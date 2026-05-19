<div class="fab-container">

    <button class="fab-main" id="fabToggle">
        <i class="fa-solid fa-plus"></i>
    </button>

    <div class="fab-menu" id="fabMenu">

        <a href="<?= $base_url ?>task.php" class="fab-item">
            <i class="fa-solid fa-list-check"></i>
            <span>Tugas</span>
        </a>

        <a href="<?= $base_url ?>money_plan.php" class="fab-item">
            <i class="fa-solid fa-wallet"></i>
            <span>Keuangan</span>
        </a>

        <a href="<?= $base_url ?>notes.php" class="fab-item">
            <i class="fa-solid fa-note-sticky"></i>
            <span>Catatan</span>
        </a>

        <a href="<?= $base_url ?>file_manager.php" class="fab-item">
            <i class="fa-solid fa-folder"></i>
            <span>File</span>
        </a>

    </div>
</div>

<style>
.fab-container{
    position:fixed;
    right:20px;
    bottom:90px; /* di atas floating clock */
    z-index:9999;
}

.fab-main{
    width:58px;
    height:58px;
    border:none;
    border-radius:50%;
    background:#2563eb;
    color:#fff;
    font-size:22px;
    cursor:pointer;
    box-shadow:0 8px 20px rgba(0,0,0,.25);
    transition:.3s;
}

.fab-main:hover{
    transform:scale(1.08) rotate(90deg);
}

.fab-menu{
    position:absolute;
    bottom:70px;
    right:0;
    display:flex;
    flex-direction:column;
    gap:12px;

    opacity:0;
    visibility:hidden;
    transform:translateY(10px);
    transition:.25s ease;
}

.fab-menu.show{
    opacity:1;
    visibility:visible;
    transform:translateY(0);
}

.fab-item{
    display:flex;
    align-items:center;
    gap:10px;

    background:#fff;
    color:#111827;
    text-decoration:none;

    padding:12px 16px;
    border-radius:14px;

    min-width:170px;

    box-shadow:0 8px 18px rgba(0,0,0,.12);
    border:1px solid rgba(0,0,0,.08);

    font-size:14px;
    font-weight:600;

    transition:.2s;
}

.fab-item:hover{
    transform:translateX(-5px);
    background:#f3f4f6;
}

.fab-item i{
    width:18px;
    text-align:center;
    font-size:16px;
}

@media(max-width:768px){

    .fab-container{
        right:15px;
        bottom:85px;
    }

    .fab-main{
        width:54px;
        height:54px;
    }

    .fab-item{
        min-width:150px;
        padding:11px 14px;
        font-size:13px;
    }
}
</style>

<script>
const fabToggle = document.getElementById("fabToggle");
const fabMenu = document.getElementById("fabMenu");

fabToggle.addEventListener("click", function(e){
    e.stopPropagation();
    fabMenu.classList.toggle("show");
});

document.addEventListener("click", function(){
    fabMenu.classList.remove("show");
});
</script>