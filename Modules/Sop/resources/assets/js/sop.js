import { initAllTomSelects } from '@shared/tom-select-factory.js';

document.addEventListener('alpine:init', () => {

    // ── Shared flowchart layout helpers ─────────────────────────────────────

    function flowchartLayout() {
        return {
            NODE_W: 112, NODE_H: 56,
            DIA_W:  100, DIA_H:  60,
            OVAL_W:  80, OVAL_H:  44,
            PARA_W: 100, PARA_H:  44,
            GAP_X:   44, GAP_Y: 48, START_X: 24, START_Y: 32, BRANCH_DROP: 96,
            layoutDir: 'horizontal', // 'horizontal' | 'vertical'

            buildLayout() {
                if (!this.steps || this.steps.length === 0) {
                    this.layout = { nodes: [], connectors: [] };
                    this.canvas = { width: 300, height: 120 };
                    return;
                }
                const nodes = this.computeNodes();
                const connLines = this.computeConnectors(nodes);
                if (this.layoutDir === 'vertical') {
                    const maxX = Math.max(...nodes.map(n => n.x + n.w)) + 140;
                    const maxY = Math.max(...nodes.map(n => n.y + n.h)) + this.GAP_Y + 48;
                    this.canvas = { width: Math.max(maxX, 360), height: Math.max(maxY, 200) };
                } else {
                    const maxX = Math.max(...nodes.map(n => n.x + n.w)) + this.START_X;
                    const maxY = Math.max(...nodes.map(n => n.y + n.h)) + this.BRANCH_DROP + 48;
                    this.canvas = { width: Math.max(maxX, 300), height: Math.max(maxY, 120) };
                }
                this.layout = { nodes, connectors: connLines };
            },

            computeNodes() {
                const nodes = [];
                if (this.layoutDir === 'vertical') {
                    let curY = this.START_Y;
                    const centerX = 180;
                    this.steps.forEach(step => {
                        const { w, h } = this._nodeSize(step.shape);
                        const x = centerX - w / 2;
                        nodes.push({
                            ...step, x, y: curY, w, h,
                            cx: centerX, cy: curY + h / 2,
                            shortTitle: this._shortTitle(step.title),
                        });
                        curY += h + this.GAP_Y;
                    });
                } else {
                    let cx = this.START_X;
                    this.steps.forEach(step => {
                        const { w, h } = this._nodeSize(step.shape);
                        nodes.push({
                            ...step, x: cx, y: this.START_Y, w, h,
                            cx: cx + w / 2, cy: this.START_Y + h / 2,
                            shortTitle: this._shortTitle(step.title),
                        });
                        cx += w + this.GAP_X;
                    });
                }
                return nodes;
            },

            _nodeSize(shape) {
                if (shape === 'diamond')                              return { w: this.DIA_W,  h: this.DIA_H  };
                if (shape === 'oval' || shape === 'oval_double')      return { w: this.OVAL_W, h: this.OVAL_H };
                if (shape === 'parallelogram')                        return { w: this.PARA_W, h: this.PARA_H };
                return { w: this.NODE_W, h: this.NODE_H };
            },

            _shortTitle(title) {
                return title && title.length > 15 ? title.slice(0, 14) + '…' : (title ?? '');
            },

            computeConnectors(nodes) {
                const nodeById = {};
                nodes.forEach(n => { nodeById[n.id] = n; });
                const colorMap = {
                    sequence: '#B4B2A9', yes_branch: '#639922', no_branch: '#E24B4A',
                    trigger: '#7F77DD', return: '#EF9F27', exception: '#E24B4A',
                };
                const lines = [];
                (this.connectors || []).forEach(conn => {
                    const from = nodeById[conn.from_step_id];
                    const to   = nodeById[conn.to_step_id];
                    if (!from || !to) return;
                    const color  = conn.color_hex || colorMap[conn.connector_type] || '#B4B2A9';
                    const dashed = conn.connector_type === 'trigger' || conn.connector_type === 'exception';
                    const markerColor = conn.connector_type === 'yes_branch' ? 'green'
                        : (conn.connector_type === 'no_branch' || conn.connector_type === 'exception') ? 'red'
                        : conn.connector_type === 'trigger' ? 'purple'
                        : conn.connector_type === 'return'  ? 'orange'
                        : 'gray';
                    let path, labelX, labelY;
                    if (this.layoutDir === 'vertical') {
                        // Vertical connector paths
                        if (conn.connector_type === 'no_branch') {
                            // Go right from the decision node
                            const sideX = from.x + from.w + 60;
                            path = `M${from.x + from.w},${from.cy} L${sideX},${from.cy} L${sideX},${to.cy} L${to.x + to.w},${to.cy}`;
                            labelX = sideX + 10; labelY = from.cy + 12;
                        } else if (to.y >= from.y + from.h) {
                            // Straight down
                            path = `M${from.cx},${from.y + from.h} L${to.cx},${to.y}`;
                            labelX = from.cx + 12; labelY = (from.y + from.h + to.y) / 2;
                        } else {
                            // Loop back (return/trigger) — go left, up, right
                            const loopX = Math.min(from.x, to.x) - 44;
                            path = `M${from.cx},${from.y} L${from.cx},${from.y - 18} L${loopX},${from.y - 18} L${loopX},${to.cy} L${to.x},${to.cy}`;
                            labelX = loopX - 14; labelY = (from.y + to.y) / 2;
                        }
                    } else {
                        // Horizontal connector paths (original logic)
                        if (conn.connector_type === 'no_branch') {
                            const dropY = from.y + from.h + this.BRANCH_DROP;
                            path = `M${from.cx},${from.y + from.h} L${from.cx},${dropY}`;
                            labelX = from.cx + 12; labelY = from.y + from.h + 16;
                        } else if (to.x >= from.x + from.w) {
                            path = `M${from.x + from.w},${from.cy} L${to.x},${to.cy}`;
                            labelX = (from.x + from.w + to.x) / 2; labelY = from.cy - 9;
                        } else {
                            const midY = from.y - 28;
                            path = `M${from.cx},${from.y} L${from.cx},${midY} L${to.cx},${midY} L${to.cx},${to.y}`;
                            labelX = (from.cx + to.cx) / 2; labelY = midY - 7;
                        }
                    }
                    lines.push({ ...conn, path, color, dashed, markerColor, labelX, labelY });
                });
                return lines;
            },
        };
    }

    // ── Zoom / pan helpers ───────────────────────────────────────────────────

    function zoomPan() {
        return {
            zoom: 1.0,
            panX: 0,
            panY: 0,
            isPanning: false,
            spaceDown: false,
            _panStart: null,
            _lastPinchDist: null,
            _pinchZoom: 1,

            viewBoxStr() {
                const vw = this.canvas.width / this.zoom;
                const vh = this.canvas.height / this.zoom;
                // Allow a 20% overpan so users can see edge nodes clearly
                const margin = 0.2;
                const px = Math.max(-vw * margin, Math.min(this.panX, Math.max(0, this.canvas.width - vw) + vw * margin));
                const py = Math.max(-vh * margin, Math.min(this.panY, Math.max(0, this.canvas.height - vh) + vh * margin));
                return `${px} ${py} ${vw} ${vh}`;
            },

            zoomIn() {
                this.zoom = Math.min(4, this.zoom * 1.3);
            },

            zoomOut() {
                this.zoom = Math.max(0.2, this.zoom / 1.3);
            },

            fitToScreen() {
                this.zoom = 1;
                this.panX = 0;
                this.panY = 0;
            },

            toggleLayout() {
                this.layoutDir = this.layoutDir === 'horizontal' ? 'vertical' : 'horizontal';
                this.buildLayout();
                this.fitToScreen();
            },

            // ── Pan via mouse ─────────────────────────────────────────────

            onSvgMouseDown(e) {
                // Middle mouse or space+left mouse → start panning
                if (e.button === 1 || (e.button === 0 && this.spaceDown)) {
                    e.preventDefault();
                    this.isPanning = true;
                    this._panStart = { x: e.clientX, y: e.clientY, panX: this.panX, panY: this.panY };
                }
            },

            onSvgMouseMove(e) {
                if (!this.isPanning || !this._panStart) return;
                const svg = this.$refs.svg;
                if (!svg) return;
                const rect = svg.getBoundingClientRect();
                const scaleX = (this.canvas.width / this.zoom) / rect.width;
                const scaleY = (this.canvas.height / this.zoom) / rect.height;
                this.panX = this._panStart.panX - (e.clientX - this._panStart.x) * scaleX;
                this.panY = this._panStart.panY - (e.clientY - this._panStart.y) * scaleY;
            },

            onSvgMouseUp() {
                this.isPanning = false;
                this._panStart = null;
            },

            // ── Zoom via ctrl+wheel ───────────────────────────────────────

            onSvgWheel(e) {
                if (!e.ctrlKey && !e.metaKey) return;
                e.preventDefault();
                const factor = e.deltaY > 0 ? 0.85 : 1.18;
                const svg = this.$refs.svg;
                if (svg) {
                    const rect = svg.getBoundingClientRect();
                    // Zoom towards cursor position
                    const vw = this.canvas.width / this.zoom;
                    const vh = this.canvas.height / this.zoom;
                    const cursorX = (e.clientX - rect.left) / rect.width * vw + this.panX;
                    const cursorY = (e.clientY - rect.top) / rect.height * vh + this.panY;
                    const newZoom = Math.max(0.2, Math.min(4, this.zoom * factor));
                    const newVW = this.canvas.width / newZoom;
                    const newVH = this.canvas.height / newZoom;
                    this.panX = cursorX - (e.clientX - rect.left) / rect.width * newVW;
                    this.panY = cursorY - (e.clientY - rect.top) / rect.height * newVH;
                    this.zoom = newZoom;
                } else {
                    this.zoom = Math.max(0.2, Math.min(4, this.zoom * factor));
                }
            },

            // ── Pinch-to-zoom (touch) ─────────────────────────────────────

            onTouchStart(e) {
                if (e.touches.length === 2) {
                    e.preventDefault();
                    this._lastPinchDist = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    this._pinchZoom = this.zoom;
                }
            },

            onTouchMove(e) {
                if (e.touches.length === 2 && this._lastPinchDist) {
                    e.preventDefault();
                    const dist = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    this.zoom = Math.max(0.2, Math.min(4, this._pinchZoom * (dist / this._lastPinchDist)));
                }
            },

            onTouchEnd() {
                this._lastPinchDist = null;
                this._pinchZoom = 1;
            },
        };
    }

    // ── Register keyboard shortcuts ─────────────────────────────────────────

    function registerKeys(component, onDelete) {
        const kd = (e) => {
            if (e.target.matches('input, textarea, select, [contenteditable]')) return;
            if (e.code === 'Space') { e.preventDefault(); component.spaceDown = true; }
            if (e.code === 'Escape') {
                component.selected = null;
                if ('showEditDrawer' in component) component.showEditDrawer = false;
            }
            if ((e.key === 'Delete' || e.key === 'Backspace') && component.selected) {
                e.preventDefault();
                if (onDelete) onDelete(component.selected);
            }
        };
        const ku = (e) => { if (e.code === 'Space') component.spaceDown = false; };
        window.addEventListener('keydown', kd);
        window.addEventListener('keyup', ku);
        return () => {
            window.removeEventListener('keydown', kd);
            window.removeEventListener('keyup', ku);
        };
    }

    // ── Read-only viewer ────────────────────────────────────────────────────

    Alpine.data('sopFlowchart', (dataUrl) => ({
        steps: [], connectors: [], meta: {},
        layout: { nodes: [], connectors: [] },
        canvas: { width: 800, height: 200 },
        selected: null, showDuration: false, loading: true, error: null,
        ...flowchartLayout(),
        ...zoomPan(),

        async init() {
            await this.load();
            const cleanup = registerKeys(this, null);
            this.$cleanup(cleanup);
        },

        async load() {
            try {
                const res = await fetch(dataUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                this.steps      = data.steps      ?? [];
                this.connectors = data.connectors  ?? [];
                this.meta = {
                    step_count:      data.steps?.length      ?? 0,
                    total_duration:  data.total_duration     ?? 0,
                    mandatory_count: data.mandatory_count    ?? 0,
                };
                this.buildLayout();
            } catch {
                this.error = 'Không thể tải dữ liệu flowchart.';
            } finally {
                this.loading = false;
            }
        },

        selectStep(node) {
            if (this.isPanning || this.spaceDown) return;
            this.selected = this.selected?.id === node.id ? null : node;
        },
    }));

    // ── Editor ──────────────────────────────────────────────────────────────

    Alpine.data('sopEditor', (dataUrl, opts) => ({
        // ── viewer state ──────────────────────────────────────────────
        steps: [], connectors: [], meta: {},
        layout: { nodes: [], connectors: [] },
        canvas: { width: 800, height: 200 },
        selected: null, showDuration: false, loading: true, error: null,
        ...flowchartLayout(),
        ...zoomPan(),

        // ── editor options ────────────────────────────────────────────
        locked:          opts.locked          || false,
        stepsUrl:        opts.stepsUrl        || '',
        connectorsUrl:   opts.connectorsUrl   || '',
        reorderUrl:      opts.reorderUrl      || '',
        approvedSopsUrl: opts.approvedSopsUrl || '',
        raciBaseUrl:     opts.raciBaseUrl     || '',
        usersSearchUrl:  opts.usersSearchUrl  || '',
        rolesUrl:        opts.rolesUrl        || '',

        // ── add step panel ────────────────────────────────────────────
        showAddPanel: false,
        addSaving:    false,
        addErrors:    {},
        newStep: {
            step_type: 'action', title: '', description: '',
            expected_output: '', warning_note: '',
            duration_minutes: '', is_mandatory: true, ref_sop_id: null,
        },

        // ── edit step drawer ──────────────────────────────────────────
        showEditDrawer: false,
        editSaving:     false,
        editErrors:     {},
        editingUuid:    null,
        editForm: {
            step_type: '', title: '', description: '',
            expected_output: '', warning_note: '',
            duration_minutes: '', is_mandatory: true, ref_sop_id: null,
        },

        // ── sub-SOP search ────────────────────────────────────────────
        approvedSops: [],
        subSopQuery:  '',

        // ── drawer tabs (info / raci / files) ─────────────────────────
        drawerTab: 'info',

        // ── RACI ─────────────────────────────────────────────────────
        stepRaci:          [],
        raciForm:          { assignee_type: 'user', raci_type: 'R' },
        raciSearchQ:       '',
        raciSearchResults: [],
        raciSaving:        false,
        editingStepUuid:   null,

        // ── Attachments ───────────────────────────────────────────────
        stepAttachments: [],
        uploading:       false,
        uploadError:     null,

        // ── connect mode ──────────────────────────────────────────────
        connectMode:         false,
        connectSource:       null,
        showConnectorPicker: false,
        connSaving:          false,
        selectedConn:        null,
        pendingConn: { from_step_id: null, to_step_id: null, connector_type: 'sequence', label: '' },

        // ── reorder drag & drop ───────────────────────────────────────
        dragIdx:     null,
        dragoverIdx: null,

        // ── validation ────────────────────────────────────────────────
        validationErrors: [],
        showValidation:   false,

        // ── init ─────────────────────────────────────────────────────
        async init() {
            await this.load();
            const cleanup = registerKeys(this, (node) => {
                if (!this.locked) this.deleteStep(node, null);
            });
            this.$cleanup(cleanup);
        },

        async load() {
            try {
                const res = await fetch(dataUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                this.steps      = data.steps      ?? [];
                this.connectors = data.connectors  ?? [];
                this.meta = {
                    step_count:      data.steps?.length ?? 0,
                    total_duration:  data.total_duration ?? 0,
                    mandatory_count: data.mandatory_count ?? 0,
                };
                this.buildLayout();
            } catch {
                this.error = 'Không thể tải dữ liệu flowchart.';
            } finally {
                this.loading = false;
            }
        },

        async reload() {
            const res = await fetch(dataUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            this.steps      = data.steps      ?? [];
            this.connectors = data.connectors  ?? [];
            this.meta = {
                step_count:     data.steps?.length ?? 0,
                total_duration: data.total_duration ?? 0,
            };
            this.buildLayout();
            if (this.showValidation) this.runValidation();
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        // ── add step ─────────────────────────────────────────────────

        resetNewStep() {
            this.newStep = {
                step_type: 'action', title: '', description: '',
                expected_output: '', warning_note: '',
                duration_minutes: '', is_mandatory: true, ref_sop_id: null,
            };
            this.addErrors = {};
        },

        async saveNewStep() {
            this.addErrors = {};
            if (!this.newStep.title.trim()) {
                this.addErrors = { title: 'Tên bước là bắt buộc.' };
                return;
            }
            this.addSaving = true;
            try {
                const res = await fetch(this.stepsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(this.newStep),
                });
                const json = await res.json();
                if (!res.ok) { this.addErrors = json.errors ?? { title: json.message }; return; }
                this.showAddPanel = false;
                this.resetNewStep();
                await this.reload();
            } finally {
                this.addSaving = false;
            }
        },

        // ── select / edit step ────────────────────────────────────────

        selectStep(node) {
            if (this.isPanning || this.spaceDown) return;
            if (this.locked) {
                this.selected = this.selected?.id === node.id ? null : node;
                return;
            }
            if (this.connectMode) {
                this._handleConnectClick(node);
                return;
            }
            if (this.selected?.id === node.id && this.showEditDrawer) {
                this.showEditDrawer = false;
                this.selected = null;
            } else {
                this.selected = node;
                this._openEditDrawer(node);
            }
        },

        _openEditDrawer(node) {
            this.editingUuid     = node.uuid;
            this.editingStepUuid = node.uuid;
            this.editForm = {
                step_type:        node.step_type,
                title:            node.title ?? '',
                description:      node.description ?? '',
                expected_output:  node.expected_output ?? '',
                warning_note:     node.warning_note ?? '',
                duration_minutes: node.duration_minutes ?? '',
                is_mandatory:     node.is_mandatory ?? true,
                ref_sop_id:       node.ref_sop_id ?? null,
            };
            this.editErrors       = {};
            this.drawerTab        = 'info';
            this.stepRaci         = [];
            this.stepAttachments  = [];
            this.raciSearchQ      = '';
            this.raciSearchResults = [];
            this.uploadError      = null;
            this.showEditDrawer   = true;
            this.showAddPanel     = false;
            if (node.step_type === 'sub_sop') this.loadApprovedSops('');
            this.loadStepRaci();
            this.loadStepAttachments();
        },

        async saveEdit() {
            this.editErrors = {};
            if (!this.editForm.title.trim()) {
                this.editErrors = { title: 'Tên bước là bắt buộc.' };
                return;
            }
            this.editSaving = true;
            try {
                const url = this.stepsUrl + '/' + this.editingUuid;
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(this.editForm),
                });
                const json = await res.json();
                if (!res.ok) { this.editErrors = json.errors ?? { title: json.message }; return; }
                this.showEditDrawer = false;
                this.selected = null;
                await this.reload();
            } finally {
                this.editSaving = false;
            }
        },

        async deleteStep(node, event) {
            event?.stopPropagation();
            if (!confirm(`Xóa bước "${node.title}"?\nCác kết nối liên quan cũng sẽ bị xóa.`)) return;
            const url = this.stepsUrl + '/' + node.uuid;
            await fetch(url, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
            });
            if (this.selected?.id === node.id)  { this.selected = null; }
            if (this.editingUuid === node.uuid)  { this.showEditDrawer = false; }
            await this.reload();
        },

        // ── RACI ─────────────────────────────────────────────────────

        async loadStepRaci() {
            if (!this.editingStepUuid || !this.raciBaseUrl) return;
            try {
                const res = await fetch(`${this.raciBaseUrl}/${this.editingStepUuid}/raci`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) this.stepRaci = await res.json();
            } catch {}
        },

        async searchRaciAssignees() {
            if (!this.raciSearchQ.trim()) { this.raciSearchResults = []; return; }
            const url = this.raciForm.assignee_type === 'user'
                ? `${this.usersSearchUrl}?q=${encodeURIComponent(this.raciSearchQ)}`
                : this.rolesUrl;
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (this.raciForm.assignee_type === 'role' && this.raciSearchQ) {
                    const q = this.raciSearchQ.toLowerCase();
                    this.raciSearchResults = data.filter(r => r.name.toLowerCase().includes(q));
                } else {
                    this.raciSearchResults = data;
                }
            } catch {}
        },

        async addRaciAssignment(assignee) {
            if (!this.editingStepUuid || this.raciSaving) return;
            this.raciSearchResults = [];
            this.raciSaving = true;
            try {
                const res = await fetch(`${this.raciBaseUrl}/${this.editingStepUuid}/raci`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        assignee_type: this.raciForm.assignee_type,
                        assignee_id:   assignee.id,
                        raci_type:     this.raciForm.raci_type,
                    }),
                });
                if (res.ok) {
                    const raci = await res.json();
                    this.stepRaci.push(raci);
                    this.raciSearchQ = '';
                    await this.reload();
                } else {
                    const j = await res.json();
                    alert(j.message || 'Không thể thêm RACI.');
                }
            } finally {
                this.raciSaving = false;
            }
        },

        async removeRaciAssignment(raci) {
            if (!this.editingStepUuid || !confirm(`Xoá phân công ${raci.raci_type}: ${raci.assignee_name}?`)) return;
            const res = await fetch(`${this.raciBaseUrl}/${this.editingStepUuid}/raci/${raci.uuid}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
            });
            if (res.ok) {
                this.stepRaci = this.stepRaci.filter(r => r.uuid !== raci.uuid);
                await this.reload();
            }
        },

        // ── Attachments ───────────────────────────────────────────────

        async loadStepAttachments() {
            if (!this.editingStepUuid || !this.raciBaseUrl) return;
            try {
                const res = await fetch(`${this.raciBaseUrl}/${this.editingStepUuid}/attachments`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) this.stepAttachments = await res.json();
            } catch {}
        },

        async handleFileUpload(event) {
            const files = Array.from(event.target.files || []);
            event.target.value = '';
            for (const f of files) { await this._uploadFile(f); }
        },

        async handleDropUpload(event) {
            const files = Array.from(event.dataTransfer?.files || []);
            for (const f of files) { await this._uploadFile(f); }
        },

        async _uploadFile(file) {
            if (!this.editingStepUuid) return;
            const maxKb = 20480;
            if (file.size > maxKb * 1024) {
                this.uploadError = `File "${file.name}" vượt quá giới hạn 20MB.`;
                return;
            }
            this.uploadError = null;
            this.uploading   = true;
            try {
                const form = new FormData();
                form.append('file', file);
                const res = await fetch(`${this.raciBaseUrl}/${this.editingStepUuid}/attachments`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                    body: form,
                });
                if (res.ok) {
                    const att = await res.json();
                    this.stepAttachments.push(att);
                    await this.reload();
                } else {
                    const j = await res.json();
                    this.uploadError = j.message || 'Không thể tải lên file.';
                }
            } catch {
                this.uploadError = 'Lỗi kết nối khi tải lên.';
            } finally {
                this.uploading = false;
            }
        },

        async deleteAttachmentItem(att) {
            if (!this.editingStepUuid || !confirm(`Xoá file "${att.file_name}"?`)) return;
            const res = await fetch(`${this.raciBaseUrl}/${this.editingStepUuid}/attachments/${att.uuid}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
            });
            if (res.ok) {
                this.stepAttachments = this.stepAttachments.filter(a => a.uuid !== att.uuid);
                await this.reload();
            }
        },

        // ── reorder drag & drop ───────────────────────────────────────

        onDragStart(event, idx) {
            this.dragIdx = idx;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', idx);
        },

        onDragOver(event, idx) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.dragoverIdx = idx;
        },

        onDragLeave() {
            this.dragoverIdx = null;
        },

        async onDrop(event, idx) {
            event.preventDefault();
            if (this.dragIdx === null || this.dragIdx === idx) {
                this.dragIdx = this.dragoverIdx = null;
                return;
            }
            const ordered = [...this.steps];
            const [moved] = ordered.splice(this.dragIdx, 1);
            ordered.splice(idx, 0, moved);
            this.dragIdx = this.dragoverIdx = null;
            this.steps = ordered;
            this.buildLayout();
            await fetch(this.reorderUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({ ids: ordered.map(s => s.id) }),
            });
            await this.reload();
        },

        // ── connectors ───────────────────────────────────────────────

        _handleConnectClick(node) {
            if (!this.connectSource) {
                this.connectSource = node;
            } else if (this.connectSource.id === node.id) {
                this.connectSource = null;
            } else {
                this.pendingConn.from_step_id = this.connectSource.id;
                this.pendingConn.to_step_id   = node.id;
                this.pendingConn.connector_type = 'sequence';
                this.pendingConn.label = '';
                this.showConnectorPicker = true;
            }
        },

        cancelConnect() {
            this.connectSource       = null;
            this.showConnectorPicker = false;
            this.pendingConn = { from_step_id: null, to_step_id: null, connector_type: 'sequence', label: '' };
        },

        async saveConnector() {
            this.connSaving = true;
            try {
                const res = await fetch(this.connectorsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(this.pendingConn),
                });
                if (res.ok) {
                    this.cancelConnect();
                    await this.reload();
                } else {
                    const json = await res.json();
                    alert(json.message || 'Không thể tạo kết nối.');
                }
            } finally {
                this.connSaving = false;
            }
        },

        async deleteConnector(conn, event) {
            event?.stopPropagation();
            if (!confirm('Xóa kết nối này?')) return;
            const url = this.connectorsUrl + '/' + conn.uuid;
            await fetch(url, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
            });
            await this.reload();
        },

        connectorTypeLabel(type) {
            return { sequence: 'Tuần tự', yes_branch: 'Nhánh Có', no_branch: 'Nhánh Không',
                     trigger: 'Kích hoạt', return: 'Quay về', exception: 'Ngoại lệ' }[type] ?? type;
        },

        // ── sub-SOP lookup ────────────────────────────────────────────

        async loadApprovedSops(q) {
            if (!this.approvedSopsUrl) return;
            const url = this.approvedSopsUrl + (q ? '?q=' + encodeURIComponent(q) : '');
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (res.ok) this.approvedSops = await res.json();
        },

        // ── validation ───────────────────────────────────────────────

        runValidation() {
            const errs = [];
            const types = this.steps.map(s => s.step_type);
            if (!types.includes('start')) errs.push('Flow chưa có bước Start.');
            if (!types.includes('end'))   errs.push('Flow chưa có bước End.');
            const decisions = this.steps.filter(s => s.step_type === 'decision');
            decisions.forEach(step => {
                const outgoing = this.connectors.filter(c => c.from_step_id === step.id).map(c => c.connector_type);
                if (!outgoing.includes('yes_branch')) errs.push(`Bước "${step.title}" thiếu nhánh Có.`);
                if (!outgoing.includes('no_branch'))  errs.push(`Bước "${step.title}" thiếu nhánh Không.`);
            });
            this.validationErrors = errs;
        },

        toggleValidation() {
            this.showValidation = !this.showValidation;
            if (this.showValidation) this.runValidation();
        },

        // ── helpers ───────────────────────────────────────────────────

        stepTypeIcon(type) {
            return { start: '●', end: '◉', action: '▬', decision: '◆',
                     sub_sop: '⊞', notification: '▱', wait: '▢' }[type] ?? '▬';
        },

        stepTypeLabel(type) {
            return { start: 'Bắt đầu', end: 'Kết thúc', action: 'Hành động',
                     decision: 'Quyết định', sub_sop: 'SOP con',
                     notification: 'Thông báo', wait: 'Chờ' }[type] ?? type;
        },

        isConnectSource(node) {
            return this.connectSource?.id === node.id;
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-sop-form]');
    if (!form) return;
    initAllTomSelects(form);
    window.initAllDatePickers?.(form);
    if (typeof initJoditAll === 'function') initJoditAll('.jodit-editor');
    _initOrgCascades(form);
});

// ── Org → owner / department / branch cascade ─────────────────────────────

function _initOrgCascades(form) {
    const orgEl = form.querySelector('#ts-organization');
    if (!orgEl) return; // orgLocked

    const deps = [...form.querySelectorAll('[data-org-api]')].map(el => ({
        el,
        ts:      el.tomselect,
        api:     el.dataset.orgApi,
        extra:   el.dataset.orgApiExtra || '',
        pending: el.dataset.selectedValue || '',
    })).filter(d => d.ts && d.api);

    if (!deps.length) return;

    const initialOrgId = orgEl.tomselect?.getValue() ?? '';
    if (initialOrgId) {
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, initialOrgId, d.extra, d.pending));
    } else {
        deps.forEach(d => d.ts.disable());
    }

    orgEl.tomselect?.on('change', (orgId) => {
        deps.forEach(d => { d.ts.clear(true); d.ts.clearOptions(); });
        if (!orgId) { deps.forEach(d => d.ts.disable()); return; }
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, orgId, d.extra, ''));
    });
}

function _loadOrgOptions(apiUrl, ts, orgId, extra, pending) {
    ts.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}${extra}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            ts.addOptions(items.map(b => ({ value: String(b.id), text: b.text })));
            ts.enable();
            if (pending) ts.setValue(String(pending), true);
        })
        .catch(() => ts.enable());
}
