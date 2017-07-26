<div>
<h3>Please Login</h3>
<form action="<?=$this->Html->url('/login')?>" method="post" enctype="application/x-www-form-urlencoded">
<div class="row">
<input type="text" name="username" value="" placeholder="Username"/>
</div>
<div class="row">
<input type="password" name="password" value="" placeholder="Password"/>
</div>
<div class="row">
<input type='submit' name="btnSubmit" value="Login" class="button"/>
</div>
</form>
</div>