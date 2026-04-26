<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field">
    @once
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.3.4/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@24.3.4/build/js/intlTelInput.min.js"></script>

    <style>
        .iti {
            width: 100%;
            display: block;
            flex-grow: 1;
        }

        .dark .iti__country-list {
            background-color: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }

        .dark .iti__country:hover,
        .dark .iti__country.iti__highlight {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .dark .iti__country-name {
            color: #e4e4e7;
        }

        .dark .iti__dial-code {
            color: #a1a1aa;
        }

        .dark .iti__divider {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dark .iti__search-input {
            background-color: #27272a !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #e4e4e7 !important;
        }

        .dark .iti__search-input::placeholder {
            color: #71717a !important;
        }

        .iti__flag-container:focus-within {
            outline: none !important;
        }

        /* إخفاء إطار الحقل الداخلي لأن الغلاف سيتكفل به */
        .fi-custom-phone-input {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }

        .fi-custom-phone-input:focus {
            ring: 0 !important;
            outline: none !important;
        }
    </style>
    @endonce

    {{-- الغلاف الخارجي الجاهز من الفلمنت (يتكفل باللون الأحمر عند الخطأ والتصميم الخارجي) --}}
    <x-filament::input.wrapper :valid="! $errors->has($getStatePath())">

        <div
            x-data="{
                state: $wire.$entangle(@js($getStatePath())),
                validations: @js($getCountryValidations()),
                instance: null,
                
                init() {
                    const input = this.$refs.phoneInput;
                    input.value = this.state || '';

                    this.instance = window.intlTelInput(input, {
                        initialCountry: @js($getDefaultCountry()),
                        onlyCountries: @js($getOnlyCountries()),
                        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.3.4/build/js/utils.js',
                        separateDialCode: true,
                    });

                    const updateState = () => { this.state = this.instance.getNumber(); };

                    // 1. منع الكتابة الزائدة في الوقت الفعلي
                    input.addEventListener('keydown', (e) => {
                        const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
                        if (allowedKeys.includes(e.key)) return;

                        const countryData = this.instance.getSelectedCountryData();
                        const rules = this.validations[countryData.iso2];

                        if (rules && rules.length) {
                            const maxDigits = rules.length - (countryData.dialCode.length + 1);
                            const currentDigits = input.value.replace(/\D/g, '');

                            // إيقاف إدخال أي رقم جديد إذا تم الوصول للحد الأقصى
                            if (currentDigits.length >= maxDigits && /^\d$/.test(e.key)) {
                                e.preventDefault();
                            }
                        }
                    });

                    // 2. معالجة النسخ واللصق (قص الرقم الزائد تلقائياً)
                    input.addEventListener('input', () => {
                        const countryData = this.instance.getSelectedCountryData();
                        const rules = this.validations[countryData.iso2];

                        if (rules && rules.length) {
                            const maxDigits = rules.length - (countryData.dialCode.length + 1);
                            const currentDigits = input.value.replace(/\D/g, '');

                            if (currentDigits.length > maxDigits) {
                                const validDigits = currentDigits.substring(0, maxDigits);
                                this.instance.setNumber('+' + countryData.dialCode + validDigits);
                            }
                        }
                        updateState();
                    });

                    // 3. تصفير الحقل عند تغيير الدولة لتجنب إرسال أرقام لدولة أخرى
                    input.addEventListener('countrychange', () => {
                     
                        updateState();
                    });

                    this.$watch('state', (value) => {
                        if (value !== this.instance.getNumber()) {
                            this.instance.setNumber(value || '');
                        }
                    });
                }
            }"
            wire:ignore
            class="w-full">

            {{-- حقل الإدخال الجاهز من الفلمنت (يتكفل بالخطوط والألوان) --}}
            <x-filament::input
                type="tel"
                x-ref="phoneInput"
                :id="$getId()"
                :disabled="$isDisabled()"
                :placeholder="$getPlaceholder()"
                :attributes="\Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())"
                class="fi-custom-phone-input" />
        </div>

    </x-filament::input.wrapper>
</x-dynamic-component>