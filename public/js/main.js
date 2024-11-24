// loading screen
$(window).load(function () {
    $("#loadingimg").fadeOut("slow");
});

// datatable intialisation

$(document).ready(function () {
    $('[data-toggle="popover"]').popover();
    // select2 intialisation
    $(".js-select-options").select2();
    // WYSIWYG summernote editor
    $("#summernote").summernote({
        height: 200,
        toolbar: [
            // [groupName, [list of button]]
            ["style", ["bold", "italic", "underline", "clear"]],
            ["font", []],
            ["fontsize", ["fontsize"]],
            ["color", ["color"]],
            ["para", ["ul", "ol", "paragraph"]],
            ["height", ["height"]],
        ],
    });
    $("#summernote2").summernote({
        height: 200,
        toolbar: [
            // [groupName, [list of button]]
            ["style", ["bold", "italic", "underline", "clear"]],
            ["font", []],
            ["fontsize", ["fontsize"]],
            ["color", ["color"]],
            ["para", ["ul", "ol", "paragraph"]],
            ["height", ["height"]],
        ],
    });
    // tooltip intialisation
    $('[data-toggle="tooltip"]').tooltip();

    // datatable options
    function initializeDataTable() {
        var currentLang = $('html').attr('lang') || 'en';
        
        return $("#Table").DataTable({
            dom: 'T<"clear">lfrtip',
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, currentLang === 'kh' ? 'ទាំងអស់' : 'All'],
            ],
            language: currentLang === 'kh' ? {
                processing: "ដំណើរការ...",
                search: "ស្វែងរក:",
                lengthMenu: "បង្ហាញ _MENU_ ធាតុ",
                info: "បង្ហាញ _START_ ដល់ _END_ នៃ _TOTAL_ ធាតុ",
                infoEmpty: "បង្ហាញ 0 ដល់ 0 នៃ 0 ធាតុ",
                infoFiltered: "(បានចម្រាញ់ចេញពី _MAX_ ធាតុសរុប)",
                infoPostFix: "",
                loadingRecords: "កំពុងផ្ទុកទិន្នន័យ...",
                zeroRecords: "មិនមានទិន្នន័យត្រូវបង្ហាញ",
                emptyTable: "មិនមានទិន្នន័យក្នុងតារាង",
                paginate: {
                    first: "ទំព័រដំបូង",
                    previous: "មុន",
                    next: "បន្ទាប់",
                    last: "ទំព័រចុងក្រោយ"
                },
                aria: {
                    sortAscending: ": ធ្វើការតម្រៀបតាមលំដាប់ឡើង",
                    sortDescending: ": ធ្វើការតម្រៀបតាមលំដាប់ចុះ"
                }
            } : {},
            tableTools: {
                sSwfPath: "https://cdn.datatables.net/tabletools/2.2.4/swf/copy_csv_xls_pdf.swf",
                bProcessing: true,
                aButtons: [
                    "xls",
                    {
                        sExtends: "pdf",
                        sPdfOrientation: "landscape",
                        sPdfMessage: "",
                    },
                    "print",
                ],
            },
        });
    }

    // Initialize DataTable
    var table = initializeDataTable();

    // Function to update DataTable language
    function updateDataTableLanguage() {
        if (table) {
            table.destroy();
        }
        table = initializeDataTable();
    }

    // Handle language change clicks
    $('.nav.navbar-nav.navbar-right .dropdown-menu a').on('click', function(e) {
        var lang = $(this).attr('href').split('/').pop();
        localStorage.setItem('appLanguage', lang);
    });

    // Check language on page load
    $(document).ready(function() {
        var currentLang = $('html').attr('lang') || 'en';
    });

    // Listen for language change event
    $(document).on('languageChanged', function(e, lang) {
        updateDataTableLanguage();
    });

    // Function to get SweetAlert button text based on language
    function getSweetAlertButtons() {
        var currentLang = $('html').attr('lang') || 'en';
        return {
            yes: currentLang === 'kh' ? 'យល់ព្រម' : 'Yes',
            cancel: currentLang === 'kh' ? 'បោះបង់' : 'Cancel'
        };
    }

    // Handle confirm dialogs
    $(document).on('click', '[data-confirm]', function(e) {
        e.preventDefault();
        var form = $(this).closest("form");
        var link = $(this).attr("href");
        var buttons = getSweetAlertButtons();
        
        swal({
            title: $(this).attr("data-confirm"),
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: buttons.yes,
            cancelButtonText: buttons.cancel,
            closeOnConfirm: true,
        }, function(confirmed) {
            if (confirmed) {
                if (form.length > 0) {
                    form.submit();
                } else if (link) {
                    window.location.href = link;
                }
            }
        });
    });

    // Update DataTable when language changes
    $(document).on('languageChanged', function(e, lang) {
        updateDataTableLanguage();
    });
});

//removing virtual keyboard on mobile and tablet
var currentState = false;

function setSize() {
    var state = $(window).width() < 961;
    if (state != currentState) {
        currentState = state;
        if (state) {
            $(".barcode").removeAttr("id");
            $(".TAX").removeAttr("id");
            $(".Remise").removeAttr("id");
        } else {
            $(".barcode").attr("id", "keyboard");
            $(".barcode").attr("id", "num01");
            $(".barcode").attr("id", "num02");
        }
    }
}

setSize();
$(window).on("resize", setSize);

// slim scroll setup
//for the product list in the left side
$(function () {
    $("#productList").slimScroll({
        height: "555px",
        alwaysVisible: true,
        railVisible: true,
    });
});
// and the right side
$(function () {
    $("#productList2").slimScroll({
        height: "740px",
        allowPageScroll: true,
        alwaysVisible: true,
        railVisible: true,
    });
});

// virtual keyboard parametres

/***************************** LOGIN form ***********/

$(".LoginInput").focusin(function () {
    $(this).find("span").animate({ opacity: "0" }, 200);
});

$(".LoginInput").focusout(function () {
    $(this).find("span").animate({ opacity: "1" }, 300);
});

/******** passwors confirmation validation ****************/

var password = document.getElementById("password"),
    confirm_password = document.getElementById("confirm_password");

function validatePassword() {
    if (password.value != confirm_password.value) {
        confirm_password.setCustomValidity("Passwords Don't Match");
    } else {
        confirm_password.setCustomValidity("");
    }
}

if (password) password.onchange = validatePassword;
if (confirm_password) confirm_password.onkeyup = validatePassword;

/************************* modal shifting fix ****************************/

$(document.body)
    .on("show.bs.modal", function () {
        if (this.clientHeight <= window.innerHeight) {
            return;
        }
        // Get scrollbar width
        var scrollbarWidth = getScrollBarWidth();
        if (scrollbarWidth) {
            $(document.body).css("padding-right", scrollbarWidth);
            $(".navbar-fixed-top").css("padding-right", scrollbarWidth);
        }
    })
    .on("hidden.bs.modal", function () {
        $(document.body).css("padding-right", 0);
        $(".navbar-fixed-top").css("padding-right", 0);
    });

function getScrollBarWidth() {
    var inner = document.createElement("p");
    inner.style.width = "100%";
    inner.style.height = "200px";

    var outer = document.createElement("div");
    outer.style.position = "absolute";
    outer.style.top = "0px";
    outer.style.left = "0px";
    outer.style.visibility = "hidden";
    outer.style.width = "200px";
    outer.style.height = "150px";
    outer.style.overflow = "hidden";
    outer.appendChild(inner);

    document.body.appendChild(outer);
    var w1 = inner.offsetWidth;
    outer.style.overflow = "scroll";
    var w2 = inner.offsetWidth;
    if (w1 == w2) w2 = outer.clientWidth;

    document.body.removeChild(outer);

    return w1 - w2;
}
