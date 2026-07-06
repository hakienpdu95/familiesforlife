/**
 * pages/org-chart-show.js
 * Sơ đồ tổ chức: render cây từ employees.manager_id.
 * Dùng JS render để hỗ trợ cây đệ quy n cấp — không dùng Alpine x-for đệ quy.
 */

// ── Escape helper ─────────────────────────────────────────────────────────────

function occHtmlEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Tree state: open/closed tracking ─────────────────────────────────────────

var _occOpenIds  = new Set();  // nodes that are open (have visible children)
var _occCfg      = {};         // display config from backend
var _occNodes    = [];         // root nodes
var _occRootEl   = null;       // DOM container

// ── Render a single node card ─────────────────────────────────────────────────

function occBuildCard(node, isRoot) {
    var hasChildren  = node.children && node.children.length > 0;
    var open         = _occOpenIds.has(node.id);
    var cardClass    = 'occ-card' + (isRoot ? ' is-root' : '') + (node._has_more ? ' has-more' : '');

    var html = '<div class="occ-node-wrap" data-node-id="' + node.id + '">'
             + '<div class="' + cardClass + '">';

    // Avatar
    if (_occCfg.show_avatar) {
        if (node.avatar_url) {
            html += '<div class="occ-avatar"><img src="' + occHtmlEsc(node.avatar_url) + '" alt="' + occHtmlEsc(node.full_name) + '" loading="lazy"/></div>';
        } else {
            var initials = node.full_name ? node.full_name.charAt(0).toUpperCase() : '?';
            html += '<div class="occ-avatar">' + occHtmlEsc(initials) + '</div>';
        }
    }

    // Info
    html += '<div class="occ-info">'
          + '<p class="occ-name">' + occHtmlEsc(node.full_name) + '</p>';

    if (_occCfg.show_employee_code && node.employee_code) {
        html += '<p class="occ-code">' + occHtmlEsc(node.employee_code) + '</p>';
    }
    if (_occCfg.show_job_title && node.job_title) {
        html += '<p class="occ-title">' + occHtmlEsc(node.job_title) + '</p>';
    }
    if (_occCfg.show_department && node.dept_name) {
        html += '<p class="occ-dept">' + occHtmlEsc(node.dept_name) + '</p>';
    }
    if (_occCfg.show_branch && node.branch_name) {
        html += '<p class="occ-branch">' + occHtmlEsc(node.branch_name) + '</p>';
    }

    html += '</div>'; // occ-info

    // Toggle button
    if (hasChildren) {
        html += '<button class="occ-toggle' + (open ? ' open' : '') + '"'
              + ' data-toggle-id="' + node.id + '" title="' + (open ? 'Thu gọn' : 'Mở rộng') + '">'
              + '<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>'
              + '<span>' + node.children.length + '</span>'
              + '</button>';
    }

    // Has-more indicator
    if (node._has_more) {
        html += '<div class="occ-more" title="Còn cấp con — tăng max_depth để xem">…</div>';
    }

    html += '</div>'; // occ-card

    // Children subtree
    if (hasChildren && open) {
        html += '<div class="occ-children-wrap"><div class="occ-children">';
        node.children.forEach(function (child) {
            html += occBuildCard(child, false);
        });
        html += '</div></div>';
    }

    html += '</div>'; // occ-node-wrap
    return html;
}

// ── Full tree render ──────────────────────────────────────────────────────────

function occRenderTree() {
    if (!_occRootEl) return;
    var html = '';
    _occNodes.forEach(function (node) { html += occBuildCard(node, true); });
    _occRootEl.innerHTML = html;
}

// ── Global toggle handler (event delegation on root) ─────────────────────────

function occSetupDelegation() {
    if (!_occRootEl) return;
    _occRootEl.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-toggle-id]');
        if (!btn) return;
        var id = parseInt(btn.dataset.toggleId, 10);
        if (_occOpenIds.has(id)) {
            _occOpenIds.delete(id);
        } else {
            _occOpenIds.add(id);
        }
        occRenderTree();
    });
}

// ── Expand / collapse all ─────────────────────────────────────────────────────

function occCollectIds(nodes) {
    var ids = [];
    nodes.forEach(function (n) {
        if (n.children && n.children.length) {
            ids.push(n.id);
            occCollectIds(n.children).forEach(function (id) { ids.push(id); });
        }
    });
    return ids;
}

window.occExpandAll = function () {
    occCollectIds(_occNodes).forEach(function (id) { _occOpenIds.add(id); });
    occRenderTree();
};

window.occCollapseAll = function () {
    _occOpenIds.clear();
    occRenderTree();
};

// ── Init open state from config ───────────────────────────────────────────────

function occInitOpenState(nodes, expandDefault) {
    nodes.forEach(function (node) {
        if (expandDefault && node.children && node.children.length) {
            _occOpenIds.add(node.id);
            occInitOpenState(node.children, expandDefault);
        }
    });
}

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', function () {

    Alpine.data('orgChartView', function (opts) {
        var treeData   = opts.treeData   || { nodes: [], total: 0, config: {} };
        var treeApiUrl = opts.treeApiUrl || '';

        return {
            loading: false,
            error:   null,

            init: function () {
                _occCfg   = treeData.config || {};
                _occNodes = treeData.nodes  || [];

                document.addEventListener('DOMContentLoaded', function () {
                    _occRootEl = document.getElementById('occ-tree-root');
                    occInitOpenState(_occNodes, _occCfg.expand_by_default);
                    occSetupDelegation();
                    occRenderTree();
                }, { once: true });
            },

            reload: function () {
                var self = this;
                if (!treeApiUrl) return;
                self.loading = true;
                self.error   = null;

                fetch(treeApiUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept':           'application/json',
                    },
                })
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(function (data) {
                        _occCfg   = data.config || _occCfg;
                        _occNodes = data.nodes  || [];
                        _occOpenIds.clear();
                        occInitOpenState(_occNodes, _occCfg.expand_by_default);
                        occRenderTree();
                        var totalEl = document.getElementById('occ-total');
                        if (totalEl) totalEl.textContent = data.total || 0;
                        self.loading = false;
                    })
                    .catch(function (err) {
                        self.error   = 'Không thể tải dữ liệu sơ đồ: ' + err.message;
                        self.loading = false;
                    });
            },
        };
    });
});
