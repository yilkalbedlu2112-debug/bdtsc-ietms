<div class="container mt-5 text-center">
    <h2>እንኳን ደህና መጡ፣ <?php echo $_SESSION['full_name']; ?></h2>
    <p class="text-muted">እባክዎ የሚፈልጉትን የሪፖርት ዓይነት ይምረጡ</p>
    
    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary">የዕለት ምርት ሪፖርት</h5>
                    <p>የዛሬውን የሥራ ውጤት (Quantity) ለመመዝገብ እዚህ ይግቡ።</p>
                    <a href="report_production.php" class="btn btn-primary">ምርት ሪፖርት አድርግ</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-danger">የብልሽት ሪፖርት</h5>
                    <p>ማሽኑ ላይ ችግር ካለ ለጥገና ክፍል ጥያቄ ይላኩ።</p>
                    <a href="request_maintenance.php" class="btn btn-danger">ብልሽት ሪፖርት አድርግ</a>
                </div>
            </div>
        </div>
    </div>
</div>