<?php include 'header.php'; ?>
<!-- Web Parity: Digital Gift Cards -->
<div class="container my-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-4 fw-bold text-uppercase mb-3">Noir Gift Card</h1>
            <p class="lead text-muted mb-5">Give the gift of luxury. Instantly delivered via email.</p>
            
            <div class="card bg-dark text-white border-0 rounded-4 overflow-hidden mb-5" style="height: 250px;">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <h3 class="fw-bold m-0">NOIR & CO.</h3>
                        <i class="bi bi-gift fs-3"></i>
                    </div>
                    <div class="text-start">
                        <h4 class="mb-1">E-Gift Card</h4>
                        <p class="text-white-50 m-0">Redeemable online and in-app</p>
                    </div>
                </div>
            </div>

            <form>
                <div class="mb-4 text-start">
                    <label class="form-label fw-bold">Select Amount</label>
                    <div class="d-flex gap-2 justify-content-between">
                        <button type="button" class="btn btn-outline-dark flex-grow-1"><?= format_currency(10000) ?></button>
                        <button type="button" class="btn btn-dark flex-grow-1"><?= format_currency(25000) ?></button>
                        <button type="button" class="btn btn-outline-dark flex-grow-1"><?= format_currency(50000) ?></button>
                        <button type="button" class="btn btn-outline-dark flex-grow-1">Custom</button>
                    </div>
                </div>
                
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">Recipient Email</label>
                    <input type="email" class="form-control rounded-0 p-3" placeholder="friend@example.com">
                </div>

                <div class="mb-4 text-start">
                    <label class="form-label fw-bold">Personal Message (Optional)</label>
                    <textarea class="form-control rounded-0" rows="3" placeholder="Happy Birthday!"></textarea>
                </div>

                <button class="btn btn-dark w-100 rounded-0 py-3 text-uppercase fw-bold">Purchase Gift Card</button>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
