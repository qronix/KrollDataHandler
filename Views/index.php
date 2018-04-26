<?php
    include_once ("../Includes/header.php");
?>


<div class="container" id="mainContent">
    <div class="container">
        <h2 id="controllerTitle">Kroll Data Handler</h2>
        <div class="container" id="controlArea">
            <a href="" class="btn" id="begin">Begin Import</a>
        </div>
        <div class="container" id="outputArea">
        </div>
    </div>
</div>
<script type="text/javascript">
    function init(){
        var startBtn = document.getElementById('begin');
        startBtn.addEventListener('click',function(event){
            processKroll();
            event.preventDefault();
        });
    }

    function processKroll(){
       var xhttp = new XMLHttpRequest();
       var outputArea = document.getElementById('outputArea');
       xhttp.onreadystatechange = function () {
           if(this.readyState===4 && this.status===200){
               outputArea.innerHTML = this.responseText;
           }
       };
       xhttp.open('POST','../Core/fileHandler.php',true);
       xhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
       xhttp.send("action=start");
    }

    document.onload = init();
</script>
