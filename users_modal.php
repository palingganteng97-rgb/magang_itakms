<?php
// Modal untuk CRUD users (Add/Edit)
?>

<!-- Modal User Create/Update -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="userForm" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create" />
                    <input type="hidden" name="id" id="formId" value="" />

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="nama" id="fieldNama" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="fieldUsername" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="fieldEmail" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" name="telepon" id="fieldTelepon" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="fieldStatus" required>
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role_id" id="fieldRoleId" required>
                                <option value="1">Super Admin</option>
                                <option value="2">Admin IT</option>
                                <option value="3">Teknisi</option>
                                <option value="4">Viewer</option>
                            </select>
                        </div>




                        <div class="col-md-6 d-flex align-items-end">
                            <div class="w-100">
                                <div class="alert alert-danger py-2 mb-0 d-none" id="formError"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitUser">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

