<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{$website_title}</title>
        <!-- ico -->
		<link rel="icon" type="image/vnd.microsoft.icon" href="img/Archive.ico">
		<link rel="shortcut icon" type="image/x-icon" href="img/Archive.ico">
		
        <!-- Bootstrap -->
        <link href="css/bootstrap.min.css" rel="stylesheet">
        
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        	
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="css/bootstrap.min.css">
        
        <!-- Optional theme -->
        <link rel="stylesheet" href="css/bootstrap-theme.min.css">
        
		<!-- Own theme -->
		<link rel="stylesheet" href="css/repository.css">
		
        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="js/jquery.min.js"></script>
        
        <!-- Latest compiled and minified JavaScript -->
        <script src="js/bootstrap.min.js"></script>
        
      
	</head>

<body>

 <!-- Fixed navbar -->
    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="navbar-header">
       
           <!-- Platzhalter / Tab -->
           <span class="navbar-brand hidden-xs">&nbsp;</span>
           
           <!-- Modul /Title name -->
           <a class="navbar-brand" href="index.php">{L_PROJECTNAME}</a>
           
           <!-- Platzhalter / Tab -->
           <span class="navbar-brand hidden-xs">&nbsp;</span>
        
          </div>
    </nav>




 
   			
   	  <!-- Content -->
      {$content}
      
   
  
   <footer class="footer">
      <div class="container">
        	<p class="text-muted">&copy; 2016 Dirk Friedenberger - <a href="http://www.frittenburger.de">www.frittenburger.de</a> - {$version} - {$date}</p>
      </div>
   </footer>
  
  
</body>
</html>
