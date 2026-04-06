document.addEventListener("DOMContentLoaded", function () {
    const currentPath = window.location.pathname;

    // Helper function to normalize paths for comparison
    // e.g., "/administrator/config_accounts.php" -> "/administrator/config_accounts"
    const normalizePath = (path) => {
        let normalized = path.replace('.php', '').replace(/\/$/, '').toLowerCase();
        if (normalized === '' || normalized === '/index') return '/dashboard';
        return normalized;
    };

    const normalizedCurrentPath = normalizePath(currentPath);

    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link, .sidebar .sub-nav .nav-link');

    sidebarLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#' || href === 'javascript:void(0);') return;

        // Create an anchor to resolve relative paths if necessary, 
        // but sidebar links are usually absolute or root-relative in this app.
        // Simple string comparison might be safer if hrefs are consistent.
        
        const normalizedHref = normalizePath(href);

        // Check for exact match or if current path ends with href (handling relative issues)
        // Adding a check to ensure we don't accidentally match root "/" against everything if href is "/"
        if (normalizedCurrentPath === normalizedHref && normalizedHref !== '') {
            
            // 1. Activate the link itself
            link.classList.add('active');

            // 2. Handle Parent Dropdowns (Sub-menus)
            const parentItem = link.closest('.nav-item');
            if (parentItem) {
                // Check if this item is inside a submenu
                const parentCollapse = parentItem.closest('.collapse');
                if (parentCollapse) {
                    parentCollapse.classList.add('show');
                    
                    // Find the trigger link for this submenu
                    const parentId = parentCollapse.getAttribute('id');
                    if (parentId) {
                        const triggerLink = document.querySelector(`a[href="#${parentId}"]`);
                        if (triggerLink) {
                            triggerLink.classList.add('active');
                            triggerLink.setAttribute('aria-expanded', 'true');
                            triggerLink.classList.remove('collapsed');
                            
                            // Check for Grandparent (e.g. Administrator > Maintenance > Item)
                            const grandParentItem = triggerLink.closest('.nav-item');
                            if(grandParentItem){
                                const grandParentCollapse = grandParentItem.closest('.collapse');
                                if(grandParentCollapse){
                                    grandParentCollapse.classList.add('show');
                                    const grandParentId = grandParentCollapse.getAttribute('id');
                                    if(grandParentId){
                                         const grandParentTrigger = document.querySelector(`a[href="#${grandParentId}"]`);
                                         if(grandParentTrigger){
                                             grandParentTrigger.classList.add('active');
                                             grandParentTrigger.setAttribute('aria-expanded', 'true');
                                             grandParentTrigger.classList.remove('collapsed');
                                         }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    });
});
