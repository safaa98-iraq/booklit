<?php

namespace Tests\Feature\Admin;

use App\Enums\QuestionLinkType;
use App\Filament\Resources\BookletResource\Pages\EditBooklet;
use App\Filament\Resources\BookletResource\RelationManagers\TopicsRelationManager;
use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Models\Booklet;
use App\Models\ContentBlock;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\BookletContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentEditingTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Booklet $booklet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->booklet = Booklet::factory()->create();
        $this->editor = User::factory()->editor()->create();
        $this->editor->assignedSubjects()->attach($this->booklet->subject_id);
        $this->actingAs($this->editor);
    }

    public function test_block_ids_survive_reordering_and_edits(): void
    {
        $service = app(BookletContentService::class);
        $topic = $service->createTopic($this->booklet, [
            'title' => 'الخلية',
            'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'تعريف الخلية', 'level' => 2]],
                ['type' => 'paragraph', 'data' => ['text' => 'الخلية وحدة الحياة.']],
                ['type' => 'list', 'data' => ['style' => 'bullet', 'items' => ['نواة', 'سايتوبلازم']]],
            ],
        ]);

        $state = $service->editorState($topic);
        [$heading, $paragraph, $list] = $state;
        $this->assertSame("نواة\nسايتوبلازم", ContentBlock::where('uuid', $list['data']['block_uuid'])->value('plain_text'));

        // Reorder, edit the paragraph, drop the heading, add a math block, clone the paragraph.
        $paragraph['data']['text'] = 'الخلية هي وحدة الحياة الأساسية.';
        $service->updateTopic($topic, ['title' => 'الخلية', 'blocks' => [
            $list,
            $paragraph,
            $paragraph, // cloned in the editor → same UUID, must become a new block
            ['type' => 'math', 'data' => ['latex' => 'E=mc^2', 'display' => true]],
        ]]);

        $blocks = $topic->blocks()->get();
        $this->assertCount(4, $blocks);
        $this->assertSame($list['data']['block_uuid'], $blocks[0]->uuid);
        $this->assertSame($paragraph['data']['block_uuid'], $blocks[1]->uuid);
        $this->assertSame('الخلية هي وحدة الحياة الأساسية.', $blocks[1]->plain_text);
        $this->assertNotSame($paragraph['data']['block_uuid'], $blocks[2]->uuid);
        $this->assertDatabaseMissing('content_blocks', ['uuid' => $heading['data']['block_uuid']]);
    }

    public function test_topic_relation_manager_creates_and_edits_blocks(): void
    {
        Livewire::test(TopicsRelationManager::class, ['ownerRecord' => $this->booklet, 'pageClass' => EditBooklet::class])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'title' => 'الانقسام الخلوي',
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'ينقسم الانقسام إلى مراحل.']],
                ],
            ])
            ->assertHasNoTableActionErrors();

        $topic = Topic::where('title', 'الانقسام الخلوي')->firstOrFail();
        $block = $topic->blocks()->firstOrFail();
        $this->assertSame('ينقسم الانقسام إلى مراحل.', $block->plain_text);

        Livewire::test(TopicsRelationManager::class, ['ownerRecord' => $this->booklet, 'pageClass' => EditBooklet::class])
            ->mountTableAction('edit', $topic)
            ->assertTableActionDataSet(['title' => 'الانقسام الخلوي'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame($block->uuid, $topic->blocks()->firstOrFail()->uuid);
    }

    public function test_question_is_created_with_options_and_passage_anchor(): void
    {
        $topic = Topic::factory()->for($this->booklet)->create();
        $block = ContentBlock::factory()->for($topic)->create(['data' => ['text' => 'الميتوكوندريا هي مصنع الطاقة في الخلية.']]);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'subject_id' => $this->booklet->subject_id,
                'source' => 'ministerial',
                'source_reference' => 'وزاري 2024 الدور الأول',
                'difficulty' => 'hard',
                'link_type' => 'topic',
            ])
            ->fillForm([
                'booklet_id' => $this->booklet->id,
            ])
            ->fillForm([
                'topic_id' => $topic->id,
                'text' => 'ما هو مصنع الطاقة في الخلية؟',
                'explanation' => 'الميتوكوندريا تنتج ATP.',
                'options' => [
                    ['text' => 'الميتوكوندريا', 'is_correct' => true],
                    ['text' => 'النواة', 'is_correct' => false],
                    ['text' => 'الرايبوسوم', 'is_correct' => false],
                ],
                'anchors' => [
                    ['content_block_id' => $block->id, 'start_offset' => 0, 'end_offset' => 13],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $question = Question::where('text', 'ما هو مصنع الطاقة في الخلية؟')->firstOrFail();
        $this->assertSame(QuestionLinkType::Topic, $question->link_type);
        $this->assertSame($this->booklet->id, $question->booklet_id);
        $this->assertCount(3, $question->options);
        $this->assertSame('الميتوكوندريا', $question->correctOption->text);
        $this->assertSame('الميتوكوندريا', $question->anchors()->first()->quoted_text);
    }

    public function test_question_requires_exactly_one_correct_option(): void
    {
        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'subject_id' => $this->booklet->subject_id,
                'source' => 'teacher',
                'difficulty' => 'easy',
                'link_type' => 'none',
                'text' => 'سؤال تجريبي',
                'options' => [
                    ['text' => 'أ', 'is_correct' => false],
                    ['text' => 'ب', 'is_correct' => false],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['options']);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'subject_id' => $this->booklet->subject_id,
                'source' => 'teacher',
                'difficulty' => 'easy',
                'link_type' => 'none',
                'text' => 'سؤال بخيار واحد',
                'options' => [['text' => 'أ', 'is_correct' => true]],
            ])
            ->call('create')
            ->assertHasFormErrors(['options']);

        $this->assertSame(0, Question::count());
    }

    public function test_editor_cannot_assign_question_to_a_foreign_subject(): void
    {
        $foreign = Booklet::factory()->create();

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'subject_id' => $foreign->subject_id,
                'source' => 'teacher',
                'difficulty' => 'easy',
                'link_type' => 'none',
                'text' => 'سؤال',
                'options' => [['text' => 'أ', 'is_correct' => true], ['text' => 'ب', 'is_correct' => false]],
            ])
            ->call('create')
            ->assertHasFormErrors(['subject_id']);
    }

    public function test_unlinking_a_question_removes_its_anchors(): void
    {
        $topic = Topic::factory()->for($this->booklet)->create();
        $block = ContentBlock::factory()->for($topic)->create();
        $question = Question::factory()->forTopic($topic)->withOptions()->create();
        $question->anchors()->create(['content_block_id' => $block->id, 'start_offset' => 0, 'end_offset' => 5]);

        Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
            ->fillForm(['link_type' => 'none'])
            ->call('save')
            ->assertHasNoFormErrors();

        $question->refresh();
        $this->assertSame(QuestionLinkType::None, $question->link_type);
        $this->assertNull($question->booklet_id);
        $this->assertSame(0, $question->anchors()->count());
    }

    public function test_anchor_detects_edited_text(): void
    {
        $topic = Topic::factory()->for($this->booklet)->create();
        $block = ContentBlock::factory()->for($topic)->create(['data' => ['text' => 'النواة تتحكم بالخلية']]);
        $question = Question::factory()->forTopic($topic)->withOptions()->create();
        $anchor = $question->anchors()->create(['content_block_id' => $block->id, 'start_offset' => 0, 'end_offset' => 6]);

        $this->assertSame('النواة', $anchor->quoted_text);
        $this->assertFalse($anchor->isStale());

        $block->update(['data' => ['text' => 'إن النواة تتحكم بالخلية']]);

        $this->assertTrue($anchor->fresh()->isStale());
    }
}
