<?php include 'header.php'; ?>
<!-- Web Parity: Subscriptions -->
<div class="container my-5 pt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-uppercase fw-bold m-0">My Subscriptions</h2>
        <button class="btn btn-outline-dark rounded-0">Browse Subscription Boxes</button>
    </div>
    
    <div class="card border-0 bg-light p-4 rounded-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <i class="bi bi-box-seam fs-1"></i>
            </div>
            <div class="col-md-6">
                <h4 class="fw-bold mb-1">The Daily Essentials Box</h4>
                <p class="text-muted mb-0">3 Premium Basic Tees (Black, White, Grey)</p>
                <p class="text-success small fw-bold mt-2"><i class="bi bi-arrow-repeat"></i> Renews Monthly</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <h4 class="fw-bold mb-3"><?= format_currency(35000) ?> / month</h4>
                <div class="d-flex gap-2 justify-content-md-end">
                    <button class="btn btn-dark rounded-0 px-4">Manage</button>
                    <button class="btn btn-outline-danger rounded-0 px-4">Pause</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-secondary rounded-0 border-0">
        <i class="bi bi-info-circle me-2"></i> Next delivery scheduled for <strong>Oct 15, 2026</strong>. You can pause or skip a month anytime before Oct 10.
    </div>
</div>
<?php include 'footer.php'; ?>
