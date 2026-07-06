/**
 * pages/kc-item-attachment.js
 * Upload và xóa file đính kèm trong trang edit/show KcItem.
 */

document.addEventListener('alpine:init', function () {
    Alpine.data('kcAttachmentManager', function (opts) {
        opts = opts || {};
        var uploadUrl = opts.uploadUrl || '';
        var maxMb     = opts.maxMb     || 50;
        var maxTotalMb = opts.maxTotalMb || 200;

        return {
            files:      [],
            uploading:  false,
            error:      '',

            init: function () {
                // Nạp danh sách file đính kèm hiện tại (từ data-files attribute hoặc server render)
                var el = document.getElementById('kc-attach-existing');
                if (el) {
                    try { this.files = JSON.parse(el.dataset.files || '[]'); } catch (_) {}
                }
            },

            get totalKb() {
                return this.files.reduce(function (sum, f) { return sum + (f.file_size_kb || 0); }, 0);
            },

            get totalLabel() {
                var kb = this.totalKb;
                return kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
            },

            onFileDrop: function (event) {
                event.preventDefault();
                var files = event.dataTransfer?.files || event.target?.files;
                if (files) {
                    Array.from(files).forEach(function (f) { this.uploadFile(f); }, this);
                }
            },

            onFileSelect: function (event) {
                var files = event.target.files;
                if (files) {
                    Array.from(files).forEach(function (f) { this.uploadFile(f); }, this);
                }
                event.target.value = '';
            },

            uploadFile: async function (file) {
                this.error = '';

                if (file.size > maxMb * 1024 * 1024) {
                    this.error = 'File "' + file.name + '" vượt quá ' + maxMb + 'MB.';
                    return;
                }

                var currentTotalKb = this.totalKb;
                var newKb          = Math.ceil(file.size / 1024);
                if ((currentTotalKb + newKb) > maxTotalMb * 1024) {
                    this.error = 'Tổng dung lượng vượt quá ' + maxTotalMb + 'MB.';
                    return;
                }

                this.uploading = true;
                var csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
                var form = new FormData();
                form.append('file', file);

                try {
                    var res  = await fetch(uploadUrl, {
                        method:  'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body:    form,
                    });
                    var data = await res.json().catch(function () { return {}; });

                    if (res.ok) {
                        this.files.push(data);
                    } else {
                        this.error = data.message || 'Upload thất bại.';
                        if (data.errors?.file) {
                            this.error = data.errors.file[0];
                        }
                    }
                } catch (e) {
                    this.error = 'Lỗi kết nối khi upload.';
                } finally {
                    this.uploading = false;
                }
            },

            deleteFile: async function (attachment, index) {
                if (!confirm('Xóa file "' + attachment.file_name + '"?')) return;

                var csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';

                try {
                    var res = await fetch(attachment.delete_url, {
                        method:  'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });

                    if (res.ok) {
                        this.files.splice(index, 1);
                    } else {
                        var data = await res.json().catch(function () { return {}; });
                        alert(data.message || 'Xóa thất bại.');
                    }
                } catch (e) {
                    alert('Lỗi kết nối.');
                }
            },

            formatSize: function (kb) {
                return kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
            },
        };
    });
});
