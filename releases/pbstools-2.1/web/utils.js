$(document).ready(function () {
    $("#select_all").click(function (){
        $(".checkbox_item").attr('checked', $("#select_all").attr('checked'));
    });
});
