/**
 * pages/n8n-connections-form.js
 * Alpine component cho panel "Token & Secret" ở trang sửa kết nối
 * (Modules/N8n/resources/views/connections/_secrets-panel.blade.php).
 *
 * spec/N8n_Integration_Technical_Specification.md §3.2 — xoay CHỌN LỌC qua AJAX, response
 * CHỈ chứa giá trị VỪA xoay → hiển thị plaintext NGAY, đúng 1 lần, mất khi rời/tải lại trang.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('n8nSecretsPanel', (serverData = {}) => {
        const { rotateUrl = '' } = serverData;

        return {
            reveal: { inbound_token: null, inbound_secret: null, outbound_secret: null },
            busy: false,

            fullInboundUrl(token) {
                return window.location.origin + '/api/n8n/in/' + token;
            },

            async copy(text) {
                try {
                    await navigator.clipboard.writeText(text);
                } catch (e) {
                    console.error('[n8n] copy failed', e);
                }
            },

            async confirmRotate(field, warningMessage) {
                if (this.busy) return;
                if (!window.confirm(warningMessage + '\n\nTiếp tục?')) return;

                this.busy = true;
                const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';

                try {
                    const res = await fetch(rotateUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ [field]: true }),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        alert(data.message || 'Xoay thất bại. Vui lòng thử lại.');
                        return;
                    }

                    Object.assign(this.reveal, data.rotated || {});
                } catch (e) {
                    console.error('[n8n] rotate failed', e);
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                } finally {
                    this.busy = false;
                }
            },
        };
    });
});
