$(document).ready(function() {
    
    $("#btnGenerateBackup").on("click", function() {
        
        // 1. Premium Confirmation (using Swal if available, else generic confirm)
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Start Backup?',
                text: "This will generate a full database export (500MB+). It may take a moment.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Yes, Start Download',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    startBackupProcess();
                }
            });
        } else {
            if(confirm("Start database backup? (500MB+)\n\nThe download will start immediately. Please do not close the page.")) {
                startBackupProcess();
            }
        }

        function startBackupProcess() {
            let btn = $("#btnGenerateBackup");
            let originalContent = btn.html();
            let status = $("#backupStatus");

            // 2. UI Updates - Spinner inside button
            btn.prop("disabled", true).html('<div class="d-flex align-items-center justify-content-center"><span class="spinner-border spinner-border-sm me-2"></span> Processing...</div>');
            
            // Status Text with gentle fade in
            status.hide().html('<div class="text-primary fw-medium"><i class="fa-solid fa-circle-notch fa-spin me-2"></i> Compressing files... Please wait...</div>').fadeIn();

            // 3. TRIGGER THE ROUTE
            window.location.href = "../../routes/administrator/systemMaintenance.route.php?action=DownloadBackup";

            // 4. Reset UI (Simulated delay since we can't track file download cleanly via simple AJAX)
            setTimeout(function() {
                // Success State
                btn.html('<i class="fa-solid fa-check me-2"></i> Started!');
                status.html('<div class="text-success fw-bold"><i class="fa-solid fa-circle-check me-2"></i> Download has started.</div>');
                
                if (typeof showToast === 'function') {
                    showToast('Backup download started successfully.', 'success');
                }

                // Restore Button after a nice delay
                setTimeout(function() {
                    btn.prop("disabled", false).html(originalContent);
                    status.fadeOut();
                }, 4000);
            }, 3000);
        }
    });

});