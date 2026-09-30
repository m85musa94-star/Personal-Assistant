<?php

// رسائل التحقق بالعربية للقواعد المستخدمة في التطبيق (ما عداها يرجع للإنجليزية).
return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'اكتب بريدًا إلكترونيًا صحيحًا.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'date' => 'تاريخ غير صالح في حقل :attribute.',
    'exists' => 'القيمة المختارة في :attribute غير صالحة.',
    'in' => 'القيمة المختارة في :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'unique' => 'قيمة :attribute مستخدمة مسبقًا.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'after_or_equal' => 'يجب أن يكون :attribute بعد :date أو مساويًا له.',
    'min' => ['numeric' => 'يجب ألا يقل :attribute عن :min.', 'string' => 'يجب ألا يقل :attribute عن :min حرفًا.', 'array' => 'يجب ألا يقل :attribute عن :min عناصر.'],
    'max' => ['numeric' => 'يجب ألا يزيد :attribute عن :max.', 'string' => 'يجب ألا يزيد :attribute عن :max حرفًا.', 'array' => 'يجب ألا يزيد :attribute عن :max عناصر.'],
    'password' => ['min' => 'كلمة المرور :min أحرف على الأقل.', 'letters' => 'يجب أن يحتوي :attribute على حرف.', 'mixed' => 'يجب أن يحتوي :attribute على حرف كبير وصغير.', 'numbers' => 'يجب أن يحتوي :attribute على رقم.', 'symbols' => 'يجب أن يحتوي :attribute على رمز.', 'uncompromised' => ':attribute ظهر في تسريب بيانات. اختر غيره.'],
    'attributes' => [
        'name' => 'الاسم', 'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور', 'current_password' => 'كلمة المرور الحالية',
        'start_date' => 'تاريخ البداية', 'end_date' => 'تاريخ النهاية', 'leave_type_id' => 'نوع الإجازة', 'employee_id' => 'الموظف',
        'check_in' => 'الحضور', 'check_out' => 'الانصراف', 'days' => 'عدد الأيام', 'annual_days' => 'الاستحقاق السنوي',
        'hire_date' => 'تاريخ التعيين', 'salary' => 'الراتب', 'decision_note' => 'سبب الرفض', 'role' => 'الدور',
    ],
];
