# **PhpEcho**

**Changelog 6.0.0**<br>
1. Code refactoring
2. As PHP is now a pretty self-describing and self-documenting language, the quantity of PHPDoc is now heavily reduced
3. Removed feature: space notation for arrays. Any space in a key is now preserved, the engine doesn't interpret them as sub-arrays anymore  
5. New feature: management of local and global vars, please note that the local always override the global ones
3. New feature: defining global values that will be available through the whole tree of blocks using: `injectVars(array $vars)`
4. New feature: defining local values after instantiating a `PhpEcho` block at once using: `setVars(array $p)`
6. Internal heavy change: there's no more copy of variables between blocks (reduce the memory footprint and increase the global performance)
7. If the current block is not able to provide a value to be rendered then the engine will automatically seek for it in the root of the tree 
8. Better management of values composed of nested array
9. Cloning a `PhpEcho` block is now possible, the cloned value keeps everything but the link to its parent block. The new one is orphan

**Changelog 5.4.1:**<br>
1. Minor bugfix in method `isArrayOfPhpEchoBlocks(mixed $p)` when `$p` is an empty array

**Changelog 5.4.0:**<br>
1. Add new abstract class `ViewBuilder` that help to manipulate abstract views as objects

**Changelog 5.3.1:**<br>
1. Add option to return natively `null` when a key doesn't exist instead of throwing an `Exception`
   By default this option is not activated. To activate, use: `PhpEcho::setNullIfNotExist(true);`; to deactivate,
   use: `PhpEcho::setNullIfNotExist(false);`

**Changelog 5.3.0:**<br>
1. Code optimization and improvement of the parameters management
2. The method `hasGlobalParam(string $name)` is now `static`
3. You can now define the seek order to get the first value either
   from the `local` or `global` context using `getAnyParam(string $name, string $seek_order = 'local'): mixed`
4. It's possible to set at once a parameter into the local and global context using `setAnyParam(string $name, mixed $value)`
4. It's possible to unset at once a parameter from the local and the global context using `unsetAnyParam(string $name)`<br>
   Test files are updated

**Changelog 5.2.1:**<br>
1. Improving the local and global parameters' management<br>
   Add new method `getAnyParam(string $name)` that will return first the local value of the parameter if defined
   or the global value instead or throw finally an exception if the parameter is unknown<br>
   Offers the possibility to unset any local or global parameter using `unsetParam(string $name)` or `unsetGlobalParam(string $name)`<br>
   You can now check if a parameter is defined either in the local or global array using `hasAnyParam(string $name)`<br>
   Test files are updated

**Changelog 5.2.0:**<br>
1. Space is not used as directory separator anymore, the only admitted directory separator is now / (slash)
   Space is now preserved in the filepath.
   Everywhere you wrote for example `new PhpEcho('block dummy_block.php');`, you must replace it with `new PhpEcho('block/dummy_block.php');`
   The same thing for `$this->renderblock('block dummy_block.php');` which must be replaced by `$this->renderBlock('block/dummy_block.php');`
   and also for `this->renderByDefault('preloader', 'block preloader.php')` which become `this->renderByDefault('preloader', 'block/preloader.php')`
   If you have previously used a space as directory separator, you'll have to review all the view files.
   If you stayed stuck with slash (/), no problems, this upgrade won't impact your code.

**Changelog 5.1.1:**<br>
1. Standard helpers are now injected once automatically

**Changelog 5.1.0:**<br>
1. The method `getHelper(string $name): Closure` is not static anymore
2. The equivalent static is now defined as `getHelperBase(string $name): Closure`
3. The method `isHelper(string $name): bool` does not throw any `Exception` anymore and only returns a strict boolean
4. Internally some code optimization and better logic segmentation: new method `getHelperDetails(string $name): array`

**Changelog 5.0.0:**<br>
1. Removing th constant `HELPER_BOUND_TO_CLASS_INSTANCE`, it's replaced by `PhpEcho::addBindableHelper`
2. Removing the constant `HELPER_RETURN_ESCAPED_DATA`. Now, the engine is able to check when data must
   be escaped and preserve the native datatype when it's safe in HTML context
2. Instead of dying silently with `null` or empty string, the engine now throws in all case an `Exception`
   You must produce a better code as it will crash on each low quality segment.
3. Add new method `renderBlock()` to link easily a child block to its parent
4. Many code improvements
5. Fully tested: the core and all helpers have been fully tested
6. Add new helper to the standard library `renderIfNotSet()` that render a default value instead
   of throwing an `Exception` for any missing key in the stored key-value pairs

**Changelog 5.0.0:**<br>
This version is a major update and breaks the compatibility with the code
written for the previous version of the engine. The changes impact mainly the code
generating the helpers. The code for the view part of your project is not impacted by the upgrade.