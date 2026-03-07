import Alpine from "alpinejs";
import axios from "axios";
import toastr from "toastr";

Alpine.data('form', () => ({
    formData: {
        email: '',       // <- was username
        password: '',
        remember: false, // optional, if you have the checkbox
    },
    validationErrors: {},
    isSubmitting: false,

    init() {
        // If you're using Laravel's default csrf meta tag, make sure axios has it:
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    },

    async submit() {
        if (this.isSubmitting) return;
        this.isSubmitting = true;
        this.validationErrors = {};

        try {
            await axios.post('/login', this.formData);
            window.location.href = '/';
        } catch (err) {
            const status = err.response?.status;
            const data = err.response?.data;

            if (status === 422) {
                // Laravel validation/auth errors
                const errors = data?.errors || {};
                this.validationErrors = errors;

                // Prefer specific messages when present (e.g., throttle or field errors)
                const fieldMsg =
                    errors.email?.[0] ||
                    errors.password?.[0] ||
                    errors['']?.[0]; // sometimes a general error sits under empty key

                if (fieldMsg) {
                    toastr.error(fieldMsg);
                } else {
                    // Fallback generic message
                    toastr.error('Email atau kata sandi salah');
                }
            } else if (status === 429) {
                // In case you use a global throttle middleware
                toastr.error('Terlalu banyak percobaan. Coba lagi nanti.');
            } else {
                toastr.error('Terjadi kesalahan saat mengirim data');
            }
        } finally {
            this.isSubmitting = false;
        }
    }
}));