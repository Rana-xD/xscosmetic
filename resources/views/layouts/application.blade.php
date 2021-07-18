<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="description" content="">
      <meta name="author" content="">
      <script src="js/app.js"></script>
      <title>Sunhay</title>
      <!-- jQuery -->
      <script type="text/javascript" src="js/jquery-2.2.2.min.js"></script>
      <script type="text/javascript" src="js/loading.js"></script>
      <!-- normalize & reset style -->
      <link rel="stylesheet" href="css/normalize.min.css"  type='text/css'>
      <link rel="stylesheet" href="css/reset.min.css"  type='text/css'>
      <link rel="stylesheet" href="css/jquery-ui.css"  type='text/css'>
      
      <!-- google lato font -->
      <link href='https://fonts.googleapis.com/css?family=Lato:400,700,900,300' rel='stylesheet' type='text/css'>
      <!-- Bootstrap Core CSS -->
      <link href="css/bootstrap.min.css" rel="stylesheet">
      <!-- bootstrap-horizon -->
      <link href="css/bootstrap-horizon.css" rel="stylesheet">
      <!-- datatable style -->
      <link href="datatables/css/dataTables.bootstrap.css" rel="stylesheet">
      <!-- font awesome -->
      <link rel="stylesheet" href="css/font-awesome.min.css">
      <!-- include summernote css-->
      <link href="css/summernote.css" rel="stylesheet">
      <!-- waves -->
      <link rel="stylesheet" href="css/waves.min.css">
      <!-- daterangepicker -->
      <link rel="stylesheet" type="text/css" href="css/daterangepicker.css" />
      <!-- css for the preview keyset extension -->
      <link href="css/keyboard-previewkeyset.css" rel="stylesheet">
      <!-- keyboard widget style -->
      <link href="css/keyboard.css" rel="stylesheet">
      <!-- Select 2 style -->
      <link href="css/select2.min.css" rel="stylesheet">
      <!-- Sweet alert swal -->
      <link rel="stylesheet" type="text/css" href="css/sweetalert.css">
      <!-- datepicker css -->
      <link rel="stylesheet" type="text/css" href="css/bootstrap-datepicker.min.css">
      <!-- Custom CSS -->
      <link href="css/Style-Light.css" rel="stylesheet">
      <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
      <![endif]-->
      
      @yield('head')
   </head>
   <body>
      <!-- Navigation -->
      <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
         <div class="container-fluid">
            <div class="navbar-header">
               <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                 <span class="sr-only">Toggle navigation</span>
                 <span class="icon-bar"></span>
                 <span class="icon-bar"></span>
                 <span class="icon-bar"></span>
               </button>
               <a class="navbar-brand" href="/"><img src="img/logo.png" alt="logo"></a>
            </div>
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
               @if (Auth::user()->role == "ADMIN")
               <ul class="nav navbar-nav">
                  <li class="flat-box"><a href="/pos"><i class="fa fa-credit-card"></i>POS</a></li>
                  <li class="dropdown">
                     <a href="#" class="dropdown-toggle flat-box" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-archive"></i> Product <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                           <li class="flat-box"><a href="/product"><i class="fa fa-archive"></i> Product (Stock) </a></li>
                           <li class="flat-box"><a href="/category"><i class="fa fa-cog"></i> Category</a></li>
                           <li class="flat-box"><a href="/unit"><i class="fa fa-bullseye"></i> Unit</a></li>
                     </ul>
                  </li>
                  <li class="dropdown">
                     <a href="#" class="dropdown-toggle flat-box" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ticket"></i> Income <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                           <li class="flat-box"><a href="/product"><i class="fa fa-file-text-o"></i> Income </a></li>
                           <li class="flat-box"><a href="/product-income"><i class="fa fa-file-archive-o"></i> Product Income</a></li>
                     </ul>
                  </li>
                 <li class="flat-box"><a href="/sale"><i class="fa fa-ticket"></i>Sales</a></li>
                 
                 {{-- <li class="flat-box"><a href="#"><i class="fa fa-line-chart"></i>Reports</a></li> --}}
               </ul>
               @endif
               <ul class="nav navbar-nav navbar-right">
                  <li class="flat-box"><a href="{{ route('logout') }}" title="LogOut"><i class="fa fa-sign-out fa-lg"></i></a></li>
               </ul>
            </div>
         </div>
         <!-- /.container -->
      </nav>
      <!-- Page Content -->

      @yield('content')


      <script src="js/main.js"></script>
      
      <!-- slim scroll script -->
      <script type="text/javascript" src="js/jquery.slimscroll.min.js"></script>
      <!-- waves material design effect -->
      <script type="text/javascript" src="js/waves.min.js"></script>
      <!-- Bootstrap Core JavaScript -->
      <script type="text/javascript" src="js/bootstrap.min.js"></script>
      <!-- keyboard widget dependencies -->
      <script type="text/javascript" src="js/jquery.keyboard.js"></script>
      <script type="text/javascript" src="js/jquery.keyboard.extension-all.js"></script>
      <script type="text/javascript" src="js/jquery.keyboard.extension-extender.js"></script>
      <script type="text/javascript" src="js/jquery.keyboard.extension-typing.js"></script>
      <script type="text/javascript" src="js/jquery.mousewheel.js"></script>
      <!-- select2 plugin script -->
      <script type="text/javascript" src="js/select2.min.js"></script>
      <!-- dalatable scripts -->
      <script src="datatables/js/jquery.dataTables.min.js"></script>
      <script src="datatables/js/dataTables.bootstrap.js"></script>
      <!-- summernote js -->
      <script src="js/summernote.js"></script>
      <!-- chart.js script -->
      <script src="js/Chart.js"></script>
      <!-- moment JS -->
      <script type="text/javascript" src="js/moment.min.js"></script>
      <!-- Include Date Range Picker -->
      <script type="text/javascript" src="js/daterangepicker.js"></script>
      <!-- Sweet Alert swal -->
      <script src="js/sweetalert.min.js"></script>
      <!-- datepicker script -->
      <script src="js/bootstrap-datepicker.min.js"></script>
      <!-- creditCardValidator script -->
      <script src="js/jquery.creditCardValidator.js"></script>
      <!-- creditCardValidator script -->
      <script src="js/credit-card-scanner.js"></script>
      <script src="js/jquery.redirect.js"></script>
      <!-- ajax form -->
      <script src="js/jquery.form.min.js"></script>
      <!-- custom script -->
   </body>
</html>
