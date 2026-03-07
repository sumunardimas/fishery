// resources/js/updateprofile.js
import Alpine from "alpinejs";
import axios from "axios";
import Swal from "sweetalert2";

Alpine.data('form_update', () => ({
  // --- fields ---
  nama: '',
  email: '',
  whatsapp: '',
  gender: '',
  // role: '',
  // committee: '',
  // institusi_id: '',
  document: null,        // new file (optional)
  document_url: null,    // existing file url (optional, for preview)

  // password (optional)
  changePassword: false,
  current_password: '',
  password: '',
  password_confirmation: '',

  // ui / errors
  validationErrors: {},
  isSubmitting: false,

  // auto-init: pull initial state if provided by Blade
  init() {
  const b64 = this.$el?.dataset?.initial;
  if (!b64) return;

  const json = atob(b64);
  const initial = JSON.parse(json);

  this.nama = initial.nama != null ? String(initial.nama) : "";
  this.email = initial.email != null ? String(initial.email) : "";
  this.whatsapp = initial.whatsapp != null ? String(initial.whatsapp) : "";
  this.gender = initial.gender != null ? String(initial.gender) : "";
  this.document_url = initial.document_url ?? null;
},

  handleFileUpload(event) {
    this.document = event.target.files[0] || null;
  },

  populateForm() {
    const fd = new FormData();
    fd.append('nama', this.nama);
    fd.append('email', this.email);
    fd.append('whatsapp', this.whatsapp);
    fd.append('gender', this.gender);
    // fd.append('role', this.role);
    // fd.append('committee', this.role === 'panitia' ? (this.committee || '') : '');
    // fd.append('institusi_id', this.institusi_id);

    // password only if changePassword checked
    if (this.changePassword) {
      fd.append('current_password', this.current_password);
      fd.append('password', this.password);
      fd.append('password_confirmation', this.password_confirmation);
    }

    if (this.document) {
      fd.append('document', this.document);
    }

    // method spoofing if needed (PUT/PATCH)
    const method = (this.$root?.dataset?.method || 'PUT').toUpperCase();
    if (method !== 'POST') {
      fd.append('_method', method);
    }

    return fd;
  },

  resetForm() {
    // don't wipe fields on update success; just clear password + file + errors
    this.document = null;
    this.current_password = '';
    this.password = '';
    this.password_confirmation = '';
    this.changePassword = false;
    this.validationErrors = {};
  },

  async submit() {
    this.isSubmitting = true;
    this.validationErrors = {};

    const formData = this.populateForm();
    const endpoint = this.$root?.dataset?.endpoint || '/profile';

    try {
      await axios.post(endpoint, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      Swal.fire('Berhasil', 'Profil berhasil diperbarui.', 'success').then(() => {
        this.resetForm();
        // refresh to reflect latest data (name/avatar/document links)
        window.location.reload();
      });

    } catch (err) {
      if (err.response?.status === 422) {
        this.validationErrors = err.response.data?.errors ?? {};
      } else {
        console.error('Unexpected error', err);
        const msg = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan data.';
        Swal.fire('Gagal', msg, 'error');
      }
    } finally {
      this.isSubmitting = false;
    }
  }
}));

