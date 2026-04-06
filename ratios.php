<!-- Ratio Tab Content -->
<div class="row" style="margin-top: 0; padding-top: -1000px; padding-left: 2rem; padding-right: 2rem;">
    <!-- Liquidity Ratios -->
    <div class="col-12" style="margin-bottom: 0.5rem;">
        <div class="card" data-aos="fade-up" data-aos-delay="800" style="margin-bottom: 0;">
            <div class="flex-wrap card-header d-flex align-items-center" style="position: relative; padding: 0.5rem 1rem;">
                <div class="d-flex align-items-center" style="flex: 0 0 auto; z-index: 1; width: 150px; flex-shrink: 0;">
                    <div class="input-group input-group-sm" style="max-width: 120px;">
                        <span class="input-group-text bg-primary text-white" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;">Year</span>
                        <select class="form-select" id="ratio-year" style="font-size: 0.75rem; padding: 0.2rem;">
                            <?php
                                $currentYear = date('Y');
                                for($i = $currentYear + 10; $i >= $currentYear - 20; $i--){
                                    $selected = ($i == $currentYear) ? 'selected' : '';
                                    echo "<option value='$i' $selected>$i</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="header-title" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); white-space: nowrap; z-index: 2;">
                    <h4 class="card-title" style="margin: 0;">LIQUIDITY RATIOS</h4>
                </div>
            </div>
            <div class="card-body" style="padding: 0.25rem 1rem;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <div id="current-ratio-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div id="acid-test-ratio-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div id="cash-ratio-gauge"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profitability Ratios -->
    <div class="col-12" style="margin-bottom: 0.5rem;">
        <div class="card" data-aos="fade-up" data-aos-delay="800" style="margin-bottom: 0;">
            <div class="flex-wrap card-header d-flex justify-content-center align-items-center" style="padding: 0.5rem 1rem;">
                <div class="header-title" style="text-align: center;">
                    <h4 class="card-title" style="margin: 0;">PROFITABILITY RATIOS</h4>
                </div>
            </div>
            <div class="card-body" style="padding: 0.25rem 1rem;">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="roa-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="roe-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="gross-margin-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="operating-margin-gauge"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Efficiency Ratios -->
    <div class="col-12" style="margin-bottom: 0.5rem;">
        <div class="card" data-aos="fade-up" data-aos-delay="800" style="margin-bottom: 0;">
            <div class="flex-wrap card-header d-flex justify-content-center align-items-center" style="padding: 0.5rem 1rem;">
                <div class="header-title" style="text-align: center;">
                    <h4 class="card-title" style="margin: 0;">EFFICIENCY RATIOS</h4>
                </div>
            </div>
            <div class="card-body" style="padding: 0.25rem 1rem;">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="asset-turnover-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="receivables-turnover-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="inventory-turnover-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div id="days-sales-inventory-gauge"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leverage Ratios -->
    <div class="col-12" style="margin-bottom: 0.5rem;">
        <div class="card" data-aos="fade-up" data-aos-delay="800" style="margin-bottom: 0;">
            <div class="flex-wrap card-header d-flex justify-content-center align-items-center" style="padding: 0.5rem 1rem;">
                <div class="header-title" style="text-align: center;">
                    <h4 class="card-title" style="margin: 0;">LEVERAGE FINANCIAL RATIOS</h4>
                </div>
            </div>
            <div class="card-body" style="padding: 0.25rem 1rem;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-center">
                            <div id="debt-ratio-gauge"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center">
                            <div id="debt-equity-gauge"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Market Per Share -->
    <div class="col-12" style="margin-bottom: 0.5rem;">
        <div class="card" data-aos="fade-up" data-aos-delay="800" style="margin-bottom: 0;">
            <div class="flex-wrap card-header d-flex justify-content-center align-items-center" style="padding: 0.5rem 1rem;">
                <div class="header-title" style="text-align: center;">
                    <h4 class="card-title" style="margin: 0;">NET MARKET PER SHARE</h4>
                </div>
            </div>
            <div class="card-body" style="padding: 0.5rem 1rem;">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div style="padding: 10px;">
                            <table class="table table-bordered text-center table-sm" style="font-size: 0.85rem; margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="padding: 0.5rem; background-color: #f8f9fa;">Item</th>
                                        <th style="padding: 0.5rem; background-color: #f8f9fa;">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold" style="padding: 0.5rem;">TOTAL EQUITY</td>
                                        <td class="text-end fw-bold" id="total-equity-value" style="padding: 0.5rem; color: #3a57e8;">₱0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="padding: 0.5rem;">TOTAL SHARES</td>
                                        <td class="text-end fw-bold" id="total-shares-value" style="padding: 0.5rem; color: #3a57e8;">0</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="text-center mt-3">
                                <small class="text-muted" style="font-size: 0.75rem;">Formula: Total Equity ÷ Total Shares</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center justify-content-center">
                        <div class="text-center p-4" style="background: linear-gradient(135deg, #3a57e8 0%, #06b6d4 100%); border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 100%; max-width: 350px;">
                            <p class="text-white mb-2 fw-bold" style="font-size: 0.9rem;">Net Market Per Share</p>
                            <h1 class="text-white fw-bold mb-0" style="font-size: 2.5rem;" id="net-market-per-share">₱0.00</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
