

  <div class="container">
		
		<form class="form-horizontal" method="POST" action="index.php">
		<fieldset>

		<!-- Form Name -->
		<legend>{L_PREFERENCES}</legend>

		{$message}
		
		<!-- Text input-->
		<div class="form-group">
		  <label class="col-md-4 control-label" for="rootpath">{L_ROOTPATH}</label>  
		  <div class="col-md-4">
		  <input id="rootpath" name="rootpath" type="text" value="{$rootpath}" class="form-control input-md">
		  </div>
		</div>
		
		<!-- Multiple Checkboxes (inline) -->
		<div class="form-group">
		  <div class="col-md-4 col-md-offset-4">
			<label class="checkbox-inline" for="rootpathcreate">
			  <input type="checkbox" name="rootpathcreate" id="rootpathcreate" value="1" {$rootpathcreate}>
			  {L_ROOTPATHCREATE}
			</label>
		    <span class="help-block">{L_ROOTPATHHELP}</span>  
		  </div>
		</div>
		
		<!-- Text input-->
		<div class="form-group">
		  <label class="col-md-4 control-label" for="rootpath">{L_REPOSITORY}</label>  
		  <div class="col-md-4">
		  <input id="repository" name="repository" type="text" value="{$repository}" class="form-control input-md">
		  </div>
		</div>
		
		<div class="form-group">
		  <label class="col-md-4 control-label" for="rootpath">{L_TOKEN}</label>  
		  <div class="col-md-4">
		  <input id="token" name="token" type="text" value="{$token}" class="form-control input-md">
		  <span class="help-block">{L_TOKENHELP}</span>  
		  </div>
		</div>
		
		<div class="form-group">
		  <label class="col-md-4 control-label" for="rootpath">{L_MAXSIZE}</label>  
		  <div class="col-md-4">
		  <input id="maxsize" name="maxsize" type="text" value="{$maxsize}" class="form-control input-md">
		  <span class="help-block">{L_MAXSIZEHELP}</span>  
		  </div>
		</div>
		
		<!-- Button -->
		<div class="form-group">
		  <div class="col-md-4 col-md-offset-4">
			<button id="singlebutton" name="singlebutton" class="btn btn-primary">{L_INSTALL}</button>
		  </div>
		</div>

		
		
		
		</fieldset>
		</form>
</div>

