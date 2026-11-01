<?php

namespace App\Filament\Forms\Components;

use App\Models\ContentBlock;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;

/**
 * Shows the selected block's text; the editor highlights a passage with the
 * mouse (or finger) and the start/end offsets are written to the sibling
 * `start_offset` / `end_offset` fields (Unicode code points, end exclusive).
 */
class PassageAnchorPicker extends Field
{
    protected string $view = 'filament.forms.components.passage-anchor-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
        $this->label('حدد النص المرتبط بالسؤال');
    }

    public function getBlockText(): ?string
    {
        return $this->evaluate(function (Get $get) {
            $id = $get('content_block_id');

            return $id ? ContentBlock::whereKey($id)->value('plain_text') : null;
        });
    }

    public function getSiblingStatePath(string $name): string
    {
        return $this->getContainer()->getStatePath().'.'.$name;
    }
}
