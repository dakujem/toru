> 📖 back to [readme](readme.md)

# Changelog

Toru follows semantic versioning.  
Please report any issues.


## v2.0

Refactored internals.  
No breaking changes for normal usage, but some observable changes for certain edge-case tooling, most notably the `omit` method parameter name change.

Replacing `IteraFn` with `Tofu` is recommended, but not required.

### Deprecations

The `IteraFn` class has been renamed to `Tofu`.  
`IteraFn` still works — it remains as a deprecated subclass of `Tofu` with an identical API — and will be removed in later versions.  
Migrate by replacing `IteraFn` with `Tofu`; no other changes are needed.
```php
// Previously:
IteraFn::filter($predicate);
// Now:
Tofu::filter($predicate);
```

> Renamed because the PHP 8.5 pipe operator turned this from a niche helper into a mainstream one, deserving a shorter name.

### Breaking changes

The `$omit` parameter of the `omit` method has been renamed to `$count`, unified across `Itera`, `Dash` and `Tofu`.  
This **only affects calls using named arguments**, e.g. `Itera::omit($input, count: 4)`; positional calls are unaffected.
```php
// Previously:
Itera::omit($input, omit: 42);
Dash::collect($input)->omit(omit: 42);
(IteraFn::omit(omit: 42))($input);   // Note: class was IteraFn, now Tofu

// Now:
Itera::omit($input, count: 42);
Dash::collect($input)->omit(count: 42);
(Tofu::omit(count: 42))($input);
```

### Minor / observable changes

These are not breaking changes for normal usage (mostly improvements), but may be observable by tooling or meta-programming:

- The methods forwarded by `Dash` and `Tofu` (previously `IteraFn`) are now declared as real methods instead of being handled via magic `__call`/`__callStatic`.
  As a result they are now reported by `method_exists()` and `ReflectionClass::getMethods()` (previously they were absent).
  This benefits IDEs and static analysis, but meta-programming that branched on their absence would behave differently.
- The forwarded methods now declare concrete return types (`array`, `int`, `Iterator`, `static`, `callable`).
  These match what was returned before, so no new `TypeError`s occur — but an overriding subclass method must now be covariant with the declared return type.


## v1.0

The initial release.
