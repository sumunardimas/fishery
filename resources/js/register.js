import Alpine from "alpinejs";
import axios from "axios";
import Swal from "sweetalert2";

Alpine.data('form_register', () => ({
  nama: '',
  email: '',
  whatsapp: '',
  password: '',
  gender: '',
  role: '',
  institusi_id: '',
  document: null,
  validationErrors: {},

  handleFileUpload(event) {
    this.document = event.target.files[0];
  },

  populateForm() {
    const formData = new FormData();
    formData.append('nama', this.nama);
    formData.append('email', this.email);
    formData.append('whatsapp', this.whatsapp);
    formData.append('password', this.password);
    formData.append('gender', this.gender);
    formData.append('role', this.role);
    formData.append('institusi_id', this.institusi_id);
    if (this.document) {
      formData.append('document', this.document);
    }
    return formData;
  },

  resetForm() {
    this.nama = '';
    this.email = '';
    this.whatsapp = '';
    this.password = '';
    this.gender = '';
    this.role = '';
    this.institusi_id = '';
    this.document = null;
    this.validationErrors = {};
  },

  async submit() {
    const formData = this.populateForm();

    try {
      await axios.post('/register', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      Swal.fire('Berhasil', 'Pendaftaran berhasil!', 'success').then(() => {
        this.resetForm();
        window.location.href = '/login';
      });

    } catch (err) {
      if (err.response?.status === 422) {
        this.validationErrors = err.response.data.errors ?? {};
      } else {
        console.error('Unexpected error', err);
        Swal.fire('Gagal', 'Terjadi kesalahan saat mengirim data.', 'error');
      }
    }
  }
}));
