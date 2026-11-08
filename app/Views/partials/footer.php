        </div>
    </div>
</div>

<?= $this->renderSection('modals') ?>

<!-- ===== JS PLUGINS ===== -->
<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/jszip/jszip.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/pdfmake/pdfmake.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/pdfmake/vfs_fonts.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.html5.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.print.min.js') ?>"></script>
<!-- SweetAlert2 -->
<script src="<?= base_url('assets/plugins/sweetalert2/sweetalert2.min.js') ?>"></script>
<!-- Toastr -->
<script src="<?= base_url('assets/plugins/toastr/toastr.min.js') ?>"></script>
<!-- Select2 -->
<script src="<?= base_url('assets/plugins/select2/js/select2.full.min.js') ?>"></script>
<!-- Moment -->
<script src="<?= base_url('assets/plugins/moment/moment.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/moment/moment-with-locales.min.js') ?>"></script>
<!-- Tempusdominus -->
<script src="<?= base_url('assets/plugins/tempus-dominus/tempus-dominus.min.js') ?>"></script>
<!-- Summernote -->
<script src="<?= base_url('assets/plugins/summernote/summernote-lite.min.js') ?>"></script>
<!-- Chart.js -->
<script src="<?= base_url('assets/plugins/chart.js/Chart.min.js') ?>"></script>
<!-- Datetime picker -->
<script src="<?= base_url('assets/plugins/jquery-datetimepicker/jquery.datetimepicker.full.js') ?>"></script>
<!-- TimeAgo -->
<script src="<?= base_url('assets/plugins/jquery-timeago/jquery.timeago.js') ?>"></script>
<!-- Toastr show -->
<script src="<?= base_url('assets/app/js/show.toast.js') ?>"></script>

<!-- App UI interactions (Modal, Tabs, Dropdown, Collapse shim) -->
<script>
window.AdminUI = (function () {
    const openModals = new Set();

    function resolveElement(target) {
        if (!target) return null;
        if (target instanceof HTMLElement) return target;
        if (target.jquery) return target.get(0);
        if (typeof target === 'string') {
            const selector = target.trim();
            if (!selector) return null;
            if (selector.startsWith('#')) return document.getElementById(selector.slice(1));
            return document.querySelector(selector);
        }
        return null;
    }

    function getTargetSelector(trigger) {
        return trigger?.getAttribute('data-ui-target')
            || trigger?.getAttribute('data-bs-target')
            || trigger?.getAttribute('href');
    }

    function syncBodyScroll() {
        document.body.style.overflow = openModals.size > 0 ? 'hidden' : '';
    }

    function showModal(target) {
        const modal = resolveElement(target);
        if (!modal) return;
        modal.classList.add('is-open', 'show');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        openModals.add(modal);
        syncBodyScroll();
        modal.dispatchEvent(new CustomEvent('app:modal-shown'));
        if (window.jQuery) window.jQuery(modal).trigger('shown');
    }

    function hideModal(target) {
        const modal = resolveElement(target);
        if (!modal) return;
        modal.classList.remove('is-open', 'show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        openModals.delete(modal);
        syncBodyScroll();
        modal.dispatchEvent(new CustomEvent('app:modal-hidden'));
        if (window.jQuery) window.jQuery(modal).trigger('hidden');
    }

    function toggleModal(target) {
        const modal = resolveElement(target);
        if (!modal) return;
        if (openModals.has(modal)) hideModal(modal);
        else showModal(modal);
    }

    function activateTab(trigger) {
        const target = getTargetSelector(trigger);
        if (!target) return;
        const pane = resolveElement(target);
        if (!pane) return;
        const tablist = trigger.closest('[role="tablist"], .nav-tabs, .nav-pills, .nav-pills-tw, .card-tw-header ul');
        if (tablist) {
            tablist.querySelectorAll('[data-ui-toggle="tab"], [data-ui-toggle="pill"], [data-bs-toggle="tab"], [data-bs-toggle="pill"]').forEach(function (btn) {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
        }
        trigger.classList.add('active');
        trigger.setAttribute('aria-selected', 'true');
        const tabContent = pane.closest('.tab-content, .tab-content-tw');
        if (tabContent) {
            tabContent.querySelectorAll('.tab-pane, .tab-pane-tw').forEach(function (tabPane) {
                tabPane.classList.remove('show', 'active');
            });
        }
        pane.classList.add('show', 'active');
    }

    function toggleDropdown(trigger) {
        const menu = trigger.nextElementSibling || trigger.parentElement?.querySelector('.dropdown-menu');
        if (!menu) return;
        document.querySelectorAll('.dropdown-menu.show').forEach(function (openMenu) {
            if (openMenu !== menu) openMenu.classList.remove('show');
        });
        menu.classList.toggle('show');
    }

    function toggleCollapse(trigger) {
        const target = getTargetSelector(trigger);
        if (!target) return;
        const panel = resolveElement(target);
        if (!panel) return;
        panel.classList.toggle('show');
        const expanded = panel.classList.contains('show');
        trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        const icon = trigger.querySelector('.bi-chevron-down, .bi-chevron-up');
        if (icon) {
            icon.classList.toggle('bi-chevron-down', !expanded);
            icon.classList.toggle('bi-chevron-up', expanded);
        }
    }

    function attachTooltip(el) {
        if (!el) return;
        const title = el.getAttribute('title');
        if (title && !el.getAttribute('aria-label')) {
            el.setAttribute('aria-label', title);
        }
    }

    function initTooltips(scope) {
        (scope || document).querySelectorAll('[data-ui-toggle="tooltip"], [data-bs-toggle="tooltip"]').forEach(attachTooltip);
    }

    document.addEventListener('click', function (e) {
        const modalTrigger = e.target.closest('[data-ui-toggle="modal"], [data-bs-toggle="modal"]');
        if (modalTrigger) {
            e.preventDefault();
            toggleModal(getTargetSelector(modalTrigger));
            return;
        }
        const dismissTrigger = e.target.closest('[data-ui-dismiss="modal"], [data-bs-dismiss="modal"]');
        if (dismissTrigger) {
            const modal = dismissTrigger.closest('.modal');
            if (modal) hideModal(modal);
            return;
        }
        const tabTrigger = e.target.closest('[data-ui-toggle="tab"], [data-ui-toggle="pill"], [data-bs-toggle="tab"], [data-bs-toggle="pill"]');
        if (tabTrigger) {
            e.preventDefault();
            activateTab(tabTrigger);
            return;
        }
        const dropdownTrigger = e.target.closest('[data-ui-toggle="dropdown"], [data-bs-toggle="dropdown"]');
        if (dropdownTrigger) {
            e.preventDefault();
            e.stopPropagation();
            toggleDropdown(dropdownTrigger);
            return;
        }
        const collapseTrigger = e.target.closest('[data-ui-toggle="collapse"], [data-bs-toggle="collapse"]');
        if (collapseTrigger) {
            e.preventDefault();
            toggleCollapse(collapseTrigger);
            return;
        }
        document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
            menu.classList.remove('show');
        });
        const modalBackdrop = e.target.closest('.modal');
        if (modalBackdrop && e.target === modalBackdrop) {
            const staticBackdrop = modalBackdrop.getAttribute('data-ui-backdrop') === 'static'
                || modalBackdrop.getAttribute('data-bs-backdrop') === 'static';
            if (!staticBackdrop) hideModal(modalBackdrop);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || openModals.size === 0) return;
        const modals = Array.from(openModals);
        hideModal(modals[modals.length - 1]);
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal').forEach(function (modal) {
            if (!modal.classList.contains('is-open')) {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
        });
        initTooltips(document);
        <?php if (session()->getFlashdata('success')): ?>
            showSuccessToast('<?= addslashes(session()->getFlashdata('success')) ?>');
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            showDangerToast('<?= addslashes(session()->getFlashdata('error')) ?>');
        <?php endif; ?>
    });

    if (window.jQuery && !window.jQuery.fn.appModal) {
        window.jQuery.fn.appModal = function (action) {
            return this.each(function () {
                if (action === 'show') {
                    showModal(this);
                    return;
                }
                if (action === 'toggle') {
                    toggleModal(this);
                    return;
                }
                hideModal(this);
            });
        };
    }

    return {
        modal: {
            open: showModal,
            close: hideModal,
            toggle: toggleModal,
        },
        tabs: {
            activate: activateTab,
        },
        dropdown: {
            toggle: toggleDropdown,
        },
        collapse: {
            toggle: toggleCollapse,
        },
        tooltip: {
            attach: attachTooltip,
            initAll: initTooltips,
        },
    };
})();

/* Bootstrap 5 API compatibility shim */
window.bootstrap = window.bootstrap || {};
window.bootstrap.Modal = function (el) {
    this.el = typeof el === 'string'
        ? document.querySelector(el)
        : (el instanceof jQuery ? el.get(0) : el);
};
window.bootstrap.Modal.prototype.show   = function () { AdminUI.modal.open(this.el);   };
window.bootstrap.Modal.prototype.hide   = function () { AdminUI.modal.close(this.el);  };
window.bootstrap.Modal.prototype.toggle = function () { AdminUI.modal.toggle(this.el); };
window.bootstrap.Modal.getInstance = function (el) { return new window.bootstrap.Modal(el); };
window.bootstrap.Modal.getOrCreateInstance = function (el) { return new window.bootstrap.Modal(el); };

/* jQuery .modal() shim */
if (window.jQuery) {
    jQuery.fn.modal = function (action) {
        return this.each(function () {
            const m = this;
            if (action === 'show')   AdminUI.modal.open(m);
            else if (action === 'toggle') AdminUI.modal.toggle(m);
            else AdminUI.modal.close(m);
        });
    };
}
</script>

<!-- Sidebar JS -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const isOpen  = !sidebar.classList.contains('-translate-x-full');
        if (window.innerWidth < 1024) {
            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }
        } else {
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-16');
            document.querySelectorAll('.sidebar-label').forEach(el => el.classList.toggle('hidden'));
        }
    }

    function closeSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    /* Sidebar treeview accordion */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.sidebar-treeview-btn').forEach(function(btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const sub = this.nextElementSibling;
                const icon = this.querySelector('.treeview-arrow');
                if (sub) {
                    const isHidden = sub.classList.contains('hidden');
                    if (isHidden) {
                        sub.classList.remove('hidden');
                        if (icon) {
                            icon.classList.add('rotate-90', 'text-blue-400');
                            icon.classList.remove('text-slate-500');
                        }
                        this.classList.add('bg-white/10', 'text-white');
                        this.classList.remove('text-slate-400');
                    } else {
                        sub.classList.add('hidden');
                        if (icon) {
                            icon.classList.remove('rotate-90', 'text-blue-400');
                            icon.classList.add('text-slate-500');
                        }
                        const hasActive = sub.querySelector('a.active-link');
                        if (!hasActive) {
                            this.classList.remove('bg-white/10', 'text-white');
                            this.classList.add('text-slate-400');
                        }
                    }
                }
            });
        });
        /* Auto-open active parent */
        document.querySelectorAll('.sidebar-submenu a.active-link').forEach(function(link) {
            const sub = link.closest('.sidebar-submenu');
            const btn = sub ? sub.previousElementSibling : null;
            if (sub) sub.classList.remove('hidden');
            if (btn) {
                const icon = btn.querySelector('.treeview-arrow');
                if (icon) {
                    icon.classList.add('rotate-90', 'text-blue-400');
                    icon.classList.remove('text-slate-500');
                }
                btn.classList.add('bg-white/10', 'text-white');
                btn.classList.remove('text-slate-400');
            }
        });
    });
</script>

<?= $this->renderSection('scripts') ?>
</body>
</html>
