<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white text-uppercase d-flex align-items-end justify-content-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert-icon lucide-triangle-alert flex-nowrap"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                <h5 class="modal-title font-weight-bold m-0 " id="deleteConfirmationModalLabel">
                    Konfirmasi Penghapusan
                </h5>
            </div>
            <div class="modal-body">
                Data yang telah dihapus tidak akan dapat dikembalikan. Apakah Anda yakin ingin menghapus <span id="deleteConfirmationModal-deleteItemName" class="font-weight-bold border-bottom border-danger"></span>?
            </div>
            <div class="modal-footer gap-3">
                <button type="button" class="btn btn-secondary m-0" data-dismiss="modal">Batal</button>
                <form id="deleteConfirmationModal-deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger text-uppercase font-weight-bold">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
