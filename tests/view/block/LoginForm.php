<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
  <label>User</label>
  <input type="text" name="login" value="<?= $this['login'] ?>"><br>
  <label>Password</label>
  <input type="password" name="pwd" value=""><br>
  <input type="submit" name="submit" value="CONNECT">
</form>
