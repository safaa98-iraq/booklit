import 'package:flutter/material.dart';

import '../core/session.dart';
import '../widgets/common.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _register = false;
  bool _busy = false;
  bool _obscure = true;

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() => _busy = true);
    try {
      if (_register) {
        await Session.instance.register(_name.text.trim(), _phone.text.trim(), _password.text);
      } else {
        await Session.instance.login(_phone.text.trim(), _password.text);
      }
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final notice = Session.instance.sessionMessage;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Form(
                key: _form,
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  Icon(Icons.menu_book_rounded, size: 72, color: theme.colorScheme.primary),
                  const SizedBox(height: 8),
                  Text('بوكلت', textAlign: TextAlign.center, style: theme.textTheme.headlineMedium!.copyWith(fontWeight: FontWeight.w800)),
                  Text('ملازم وأسئلة طب الأسنان', textAlign: TextAlign.center, style: theme.textTheme.bodyMedium!.copyWith(color: Colors.grey.shade600)),
                  const SizedBox(height: 28),
                  if (notice != null)
                    Card(
                      color: Colors.orange.shade50,
                      child: Padding(padding: const EdgeInsets.all(12), child: Text(notice, textAlign: TextAlign.center)),
                    ),
                  if (_register) ...[
                    TextFormField(
                      controller: _name,
                      decoration: const InputDecoration(labelText: 'الاسم الكامل', prefixIcon: Icon(Icons.person_outline)),
                      validator: (v) => (v ?? '').trim().length < 3 ? 'أدخل اسمك الكامل' : null,
                    ),
                    const SizedBox(height: 14),
                  ],
                  TextFormField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    textDirection: TextDirection.ltr,
                    decoration: const InputDecoration(labelText: 'رقم الهاتف', hintText: '07XXXXXXXXX', prefixIcon: Icon(Icons.phone_iphone)),
                    validator: (v) => (v ?? '').trim().length < 10 ? 'أدخل رقم هاتف صحيح' : null,
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    controller: _password,
                    obscureText: _obscure,
                    decoration: InputDecoration(
                      labelText: 'كلمة المرور',
                      prefixIcon: const Icon(Icons.lock_outline),
                      suffixIcon: IconButton(icon: Icon(_obscure ? Icons.visibility : Icons.visibility_off), onPressed: () => setState(() => _obscure = !_obscure)),
                    ),
                    validator: (v) => (v ?? '').length < (_register ? 8 : 1) ? (_register ? 'كلمة المرور 8 أحرف على الأقل' : 'أدخل كلمة المرور') : null,
                    onFieldSubmitted: (_) => _submit(),
                  ),
                  const SizedBox(height: 22),
                  FilledButton(
                    onPressed: _busy ? null : _submit,
                    child: _busy
                        ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                        : Text(_register ? 'إنشاء حساب' : 'تسجيل الدخول', style: const TextStyle(fontSize: 16)),
                  ),
                  const SizedBox(height: 8),
                  TextButton(
                    onPressed: () => setState(() => _register = !_register),
                    child: Text(_register ? 'لديك حساب؟ سجّل الدخول' : 'ليس لديك حساب؟ أنشئ حساباً جديداً'),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'ملاحظة: يرتبط الحساب بجهاز واحد فقط.',
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodySmall!.copyWith(color: Colors.grey),
                  ),
                ]),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
