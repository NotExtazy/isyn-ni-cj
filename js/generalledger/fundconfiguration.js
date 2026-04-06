$(document).ready(function () {

    let amsFunds = [];
    let glFunds = [];
    let selectedAmsFund = null;
    let selectedGlFund = null;

    loadPage();

    // ── Events ────────────────────────────────────────────────────────────────
    $('#btnAddToGL').on('click', function () {
        addToGL();
    });

    $('#btnRemoveFromGL').on('click', function () {
        removeFromGL();
    });

    $('#btnSave').on('click', function () {
        saveConfiguration();
    });

    $('#btnReset').on('click', function () {
        loadPage();
    });

    $(document).on('click', '.ams-fund-item', function () {
        $('.ams-fund-item').removeClass('selected');
        $(this).addClass('selected');
        selectedAmsFund = $(this).data('fundname');
        selectedGlFund = null;
        $('.gl-fund-item').removeClass('selected');
    });

    $(document).on('click', '.gl-fund-item', function () {
        $('.gl-fund-item').removeClass('selected');
        $(this).addClass('selected');
        selectedGlFund = $(this).data('fundname');
        selectedAmsFund = null;
        $('.ams-fund-item').removeClass('selected');
    });

    // ── Functions ─────────────────────────────────────────────────────────────
    function loadPage() {
        $('#loading').show();
        
        $.ajax({
            url: '/iSynApp-main/generalledger/fundconfiguration',
            type: 'POST',
            data: { action: 'LoadPage' },
            dataType: 'json',
            success: function (res) {
                $('#loading').hide();
                
                if (res.STATUS === 'SUCCESS') {
                    amsFunds = res.AMS_FUNDS || [];
                    glFunds = res.GL_FUNDS || [];
                    
                    renderFunds();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE || 'Failed to load funds' });
                }
            },
            error: function (xhr, status, error) {
                $('#loading').hide();
                console.error('LoadPage error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load fund configuration' });
            }
        });
    }

    function renderFunds() {
        // Render AMS Funds (exclude those already in GL)
        let amsHtml = '';
        let availableAmsFunds = amsFunds.filter(function(fund) {
            return !glFunds.some(function(glFund) {
                return glFund.fundname === fund.fundname;
            });
        });

        if (availableAmsFunds.length === 0) {
            amsHtml = '<div class="text-center text-muted py-3">All funds are configured in GL</div>';
        } else {
            $.each(availableAmsFunds, function (i, fund) {
                amsHtml += '<div class="fund-item ams-fund-item" data-fundname="' + fund.fundname + '">';
                amsHtml += '<i class="fa-solid fa-circle-dot me-2" style="color:#6c757d;"></i>';
                amsHtml += fund.fundname;
                amsHtml += '</div>';
            });
        }
        $('#amsFundsList').html(amsHtml);

        // Render GL Funds
        let glHtml = '';
        if (glFunds.length === 0) {
            glHtml = '<div class="text-center text-muted py-3">No GL funds configured</div>';
        } else {
            $.each(glFunds, function (i, fund) {
                glHtml += '<div class="fund-item gl-fund-item" data-fundname="' + fund.fundname + '">';
                glHtml += '<i class="fa-solid fa-check-circle me-2" style="color:#28a745;"></i>';
                glHtml += fund.fundname;
                glHtml += '</div>';
            });
        }
        $('#glFundsList').html(glHtml);

        // Reset selections
        selectedAmsFund = null;
        selectedGlFund = null;
    }

    function addToGL() {
        if (!selectedAmsFund) {
            Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select a fund from AMS Funds to add to GL' });
            return;
        }

        // Find the fund in amsFunds
        let fund = amsFunds.find(function(f) {
            return f.fundname === selectedAmsFund;
        });

        if (fund) {
            // Add to glFunds
            glFunds.push(fund);
            renderFunds();
            
            Swal.fire({ 
                icon: 'success', 
                title: 'Added', 
                text: fund.fundname + ' added to GL Funds',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }

    function removeFromGL() {
        if (!selectedGlFund) {
            Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select a fund from GL Funds to remove' });
            return;
        }

        // Remove from glFunds
        glFunds = glFunds.filter(function(f) {
            return f.fundname !== selectedGlFund;
        });

        renderFunds();
        
        Swal.fire({ 
            icon: 'success', 
            title: 'Removed', 
            text: selectedGlFund + ' removed from GL Funds',
            timer: 1500,
            showConfirmButton: false
        });
    }

    function saveConfiguration() {
        if (glFunds.length === 0) {
            Swal.fire({ icon: 'warning', title: 'No Funds', text: 'Please add at least one fund to GL before saving' });
            return;
        }

        Swal.fire({
            title: 'Save Configuration?',
            text: 'This will update the GL fund configuration',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading').show();

                let fundNames = glFunds.map(function(f) {
                    return f.fundname;
                });

                $.ajax({
                    url: '/iSynApp-main/generalledger/fundconfiguration',
                    type: 'POST',
                    data: { 
                        action: 'SaveConfiguration',
                        funds: JSON.stringify(fundNames)
                    },
                    dataType: 'json',
                    success: function (res) {
                        $('#loading').hide();
                        
                        if (res.STATUS === 'SUCCESS') {
                            Swal.fire({ 
                                icon: 'success', 
                                title: 'Saved', 
                                text: 'Fund configuration saved successfully' 
                            });
                            loadPage();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE || 'Failed to save configuration' });
                        }
                    },
                    error: function (xhr, status, error) {
                        $('#loading').hide();
                        console.error('Save error:', error);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save fund configuration' });
                    }
                });
            }
        });
    }
});
