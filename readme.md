# Toru 取る

[![Test Suite](https://github.com/dakujem/toru/actions/workflows/php-test.yml/badge.svg)](https://github.com/dakujem/toru/actions/workflows/php-test.yml)
[![Coverage Status](https://coveralls.io/repos/github/dakujem/toru/badge.svg?branch=trunk)](https://coveralls.io/github/dakujem/toru?branch=trunk)

Toru is a standalone tool for _iterable_ collections for simple day-to-day tasks as well as advanced optimizations.  
Most of its functionality is based on native _generators_ for efficiency.

>
> 💿 `composer require dakujem/toru`
>

The package name comes from Japanese word "toru" (取る), which may mean "to take", "to pick up" or even "to collect".

Toru provides a few common **iteration primitives** (e.g. `map`, `filter`, `tap`),
**aggregates** (e.g. `reduce`, `search`, `count`)
and utility functions (e.g. `chain`) implemented using mostly generators or efficient iterations.

Also implements **Lodash-style fluent wrapper** to simplify composition of various transformations on iterable collections.

The aim of Toru is to provide simple tool to work with the native `iterable` type*.  
Toru also enables memory-efficient operations on large datasets.

All callable parameters (_mapper_, _predicate_, _reducer_ and _effect_ functions)
always **receive keys** along with values.  
This is a key advantage over native functions like `array_map`, `array_reduce` or `array_walk`,
even `array_filter` in its default setting.

>
> \* The `iterable` is a built-in compile time type alias for `array|Traversable` encompassing all arrays and iterators,
> so it's not exactly a native type, technically speaking.
>


## Examples

Task: Iterate over multiple large arrays (or other iterable collections) with _low memory footprint_:
```php
use Dakujem\Toru\Itera;

// No memory wasted on creating a huge array.
$all = Itera::chain($collection1, $collection2, [12,3,5], $otherCollection);

foreach ($all as $key => $element) {
    // do stuff efficiently
}
```

Task: Filter and map a collection, also specifying new keys (reindexing):
```php
use Dakujem\Toru\Dash;

$mailingList = Dash::collect($mostActiveUsers)
    ->filter(
        predicate: fn(User $user): bool => $user->hasGivenMailingConsent()
    )
    ->adjust(
        values: fn(User $user) => $user->fullName(),
        keys: fn(User $user) => $user->emailAddress(),
    )
    ->limit(100);

foreach ($mailingList as $emailAddress => $recipientName) {
    $mail->addRecipient($emailAddress, $recipientName);
}
```

Task: Create a list of all files in a directory as `path => FileInfo` pairs without running out of memory:
```php
use Dakujem\Toru\Dash;

$files = Dash::wrap(
        new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir))
    )                                                                   // recursively iterate over a dir
    ->filter(fn(\SplFileInfo $fileInfo) => !$fileInfo->isDir())         // reject directories
    ->reindex(fn(\SplFileInfo $fileInfo) => $fileInfo->getPathname());  // index by the full path of the file
```


## Usage

Most of the primitives described below are implemented in all 3 forms:
1. as a static method `Itera::*(iterable $input, ...$args)`,
   for simple cases
2. as a fluent method of the `Dash` wrapper, `Dash::*(...$args): Dash`,
   best suited for fluent composition
3. as a factory method that creates partially applied callables `IteraFn::*(...$args): callable`,
   to be composed into pipelines or used as filters


Example of _filtering_ and _mapping_ a collection, then _appending_ some more already processed elements.

Usage of the individual static methods example:
```php
use Dakujem\Toru\Itera;

$filtered = Itera::filter(input: $collection, predicate: $filterFunction);
$mapped = Itera::apply(input: $filtered, values: $mapperFunction);
$merged = Itera::chain($mapped, $moreElements);
$processed = Itera::valuesOnly(input: $merged);
```

Usage of the partially applied methods example:
```php
use Dakujem\Toru\Pipeline;
use Dakujem\Toru\IteraFn;

$processed = Pipeline::through(
    $collection,
    IteraFn::filter(predicate: $filterFunction),
    IteraFn::apply(values: $mapperFunction),
    IteraFn::chain($moreElements),
    IteraFn::valuesOnly(),
);
```

Usage of the `Dash` fluent wrapper example:
```php
use Dakujem\Toru\Dash;

$processed = Dash::collect($collection)
    ->filter(predicate: $filterFunction)
    ->apply(values: $mapperFunction)
    ->chain($moreElements)
    ->valuesOnly();
```

The `$processed` collection can now be iterated over. All the above operations are applied at this point.
```php
foreach($processed as $value){
    // The filtered and mapped values from $collection will appear here,
    // followed by the elements present in $moreElements.
}
```


## API

### Chaining multiple iterables: `chain`, `append`

```php
use Dakujem\Toru\Dash;
use Dakujem\Toru\Itera;
use Dakujem\Toru\IteraFn;

Itera::chain(iterable ...$input): iterable

// `append` is only present in Dash and IteraFn classes
Dash::append(iterable ...$more): Dash
IteraFn::append(iterable ...$more): callable
```

The `chain` method creates an iterable that is composed of all the iterable call arguments.  
The resulting iterable will yield all values (preserving keys) from the first iterable, then the next, then the next, and so on.

Compared to `array_replace` (or `array_merge` or the union operator `+` on arrays) this is very memory efficient,
because it does not double the memory usage.

The `append` method appends iterables to the wrapped/input collection. It is an alias of the `chain` method.

The `append` method is present in `IteraFn` and `Dash` classes only.
Appending makes no sense in the static context of the `Itera` class as there is nothing to append to.  
In static context, use `Itera::chain` instead.


### Mapping: `map`, `adjust`, `apply`, `reindex`, `unfold`

```php
use Dakujem\Toru\Itera;

Itera::adjust(iterable $input, ?callable $values = null, ?callable $keys = null): iterable
Itera::apply(iterable $input, callable $values): iterable
Itera::map(iterable $input, callable $values): iterable
Itera::reindex(iterable $input, callable $keys): iterable
Itera::unfold(iterable $input, callable $mapper): iterable
```

The `adjust` method allows to map both values and keys.  
The `apply` method maps values only,  
and the `reindex` method allows mapping keys (indexes).

Do not confuse the `map` method with the native `array_map`, the native function has different interface.
Instead, prefer to use the `apply` method to map values.
The `map` method is an alias of the `apply` method.

For each of these methods, all mapping callables receive the current key as the second argument.  
The signature of the mappers is always
```php
fn(mixed $value, mixed $key): mixed
```

The `unfold` methods allows mapping and/or flattening matrices one level.  
One niche trick to map _both values and keys_ using a _single callable_ with `unfold`
is to return a single key-value pair (an array containing a single element with a specified key), like so:
```php
use Dakujem\Toru\Itera;

Itera::unfold(
    [1,2,3,4],
    fn($value, $key) => ['double value for key ' . $key => $value * 2],
)

// or "produce new index and key based on the original value"
Itera::unfold(
    ['one:1', 'two:2', 'three:3'],
    function(string $value) {
        [$name, $index] = split(':', $value); // there could be a fairly complex regex here
        return [$index => $name];
    },
)
```


### Reducing: `reduce`

The reducer signature is
```php
fn(mixed $carry, mixed $value, mixed $key): mixed
```

🚧 ... TODO


### Filtering: `filter`

Create a generator that yields only the items of the input collection that the predicate returns _truthy_ for.

```php
use Dakujem\Toru\Itera;

Itera::filter(iterable $input, callable $predicate): iterable
```

Accept and eliminate elements based on a callable predicate.  
When the predicate returns _truthy_, the element is accepted and yielded.  
When the predicate returns _falsey_, the element is rejected and skipped.

The predicate signature is
```php
fn(mixed $value, mixed $key): bool
```

Similar to `array_filter`, `iter\filter`.

>
> Sidenote
> 
> Native `CallbackFilterIterator` may be used for similar results:
> ```php
> new CallbackFilterIterator(Itera::toIterator($input), $predicate)
> ```
>


### Searching: `search`, `searchOrFail`, `firstValue`, `firstKey`, `firstValueOrDefault`, `firstKeyOrDefault`

```php
use Dakujem\Toru\Itera;

Itera::search(iterable $input, callable $predicate, mixed $default = null): mixed
Itera::searchOrFail(iterable $input, callable $predicate): mixed
Itera::firstValue(iterable $input): mixed
Itera::firstKey(iterable $input): mixed
Itera::firstValueOrDefault(iterable $input, mixed $default = null): mixed
Itera::firstKeyOrDefault(iterable $input, mixed $default = null): mixed
```

Search for the first element that the predicate returns _truthy_ for.

`search` returns the default value if no matching element is found, while `searchOrFail` throws.

The `firstKey` and `firstValue` methods throw when an empty collection is on the input,
while the `*OrDefault` variants return the specified default value in such a case.

The predicate signature is
```php
fn(mixed $value, mixed $key): bool
```


### Slicing: `slice`, `limit`, `omit`

```php
use Dakujem\Toru\Itera;

Itera::limit(iterable $input, int $limit): iterable
Itera::omit(iterable $input, int $omit): iterable
Itera::slice(iterable $input, int $offset, int $limit): iterable
```

Limit the number of yielded elements with `limit`,
skip certain number of elements from the beginning with `omit`,
or use `slice` to combine both `omit` and `limit` into a single call.  
Keys will be preserved.

Passing zero or negative value to `$limit` yields an empty collection,
passing zero or negative values to `$omit`/`$offset` yields the full set.


### Alterations: `valuesOnly`, `keysOnly`, `flip`

🚧 ... TODO

### Conversions: `toArray`, `toArrayValues`, `toIterator`, `ensureTraversable`

🚧 ... TODO

### Tapping: `tap`, `each`

The signature of the effect function is
```php
fn(mixed $value, mixed $key): void
```

🚧 ... TODO


### Producing: `make`, `produce`, `repeat`, `loop`, `replicate`

🚧 ... TODO


## Lazy evaluation

Generator functions are lazy by nature. Calling the `adjust`, `map`, `chain`, even `filter` or `flip` functions,
or even chaining them,
will only decorate the input collection and return a new generator instance.

```php
use Dakujem\Toru\Itera;

$collection = Itera::apply(Itera::filter($input, $filter), $mapper);
// NOTHING has been evaluated at this point. The evaluation of $filter and $mapper callables begins with the first iteration.
foreach($collection as $key => $value) {
    // Only at this point the mapper and filter functions are called, once per element of the input collection.
    /* ... */
}
```

If the iteration is terminated before the whole collection has been iterated over,
the callables will NOT be called for the remaining elements.


## Using keys (indexes)

Callable parameters of all the methods (_mapper_, _predicate_, _reducer_ and _effect_ functions)
always receive keys along with values.

This is a key advantage over native functions like `array_map`, `array_reduce` or `array_walk`,
even `array_filter` in its default setting.

Instead of
```php
$mapper = fn($value, $key) => /* ... */;
$predicate = fn($value, $key): bool => /* ... */;

$collection = array_filter(array_map($mapper, $array, array_keys($array)), $predicate, ARRAY_FILTER_USE_BOTH);
```
it may be more convenient to
```php
use Dakujem\Toru\Dash;

$mapper = fn($value, $key) => /* ... */;
$predicate = fn($value, $key): bool => /* ... */;

$collection = Dash::wrap($array)->map($mapper)->filter($predicate);
```

With `array_reduce` this is even more convoluted.  
One way to do it is to transform the array values to include the indexes and to alter the reducer to account for the changed data type.
```php
$myActualReducer = fn($carry, $value, $key) => /**/;

// Transform the array into a form that includes keys
$transformedArray = array_map(function($key, $value) {
    return [$value, $key];
}, array_keys($array), $array);

// Apply array_reduce
$result = array_reduce($transformedArray, function($carry, array $valueAndKey) use ($myActualReducer){ 
    [$value, $key] = $valueAndKey;
    return $myActualReducer($carry, $value, $key);
});
```
Here, the solution may be even more concise
```php
use Dakujem\Toru\Itera;

$myActualReducer = fn($carry, $value, $key) => /**/;

$result = Itera::reduce($array, $myActualReducer);
```


## Custom transformations

To support custom transformation without interrupting a fluent chain when using `Dash`,
two methods are provided:

- `Dash::alter` expects a decorator function returning an altered iterable collection
- `Dash::aggregate` expects an aggregate function that immediately runs and terminates the fluent chain

`Dash::alter` wraps the return value of the decorator into a new `Dash` instance, allowing to continue the call chain.

```php
use Dakujem\Toru\Dash;

Dash::collect(['zero', 'one', 'two', 'three',])
    ->alter(function (iterable $collection): iterable {
        foreach ($collection as $k => $v) {
            yield $k * 2 => $v . ' suffix';
        }
    })
    ->alter(function (iterable $collection): iterable {
        foreach ($collection as $k => $v) {
            yield $k + 1 => 'prefix ' . $v;
        }
    })
    ->filter( /* ... */ )
    ->map( /* ... */ );
```

`Dash::aggregate` returns any value produced by the callable parameter, without wrapping it into a new `Dash` instance.

Missing a "key sum" function?
```php
use Dakujem\Toru\Dash;

$keySum = Dash::collect($input)
    ->filter( /* ... */ )
    ->aggregate(function (iterable $collection): int {
        $keySum = 0;
        foreach ($collection as $k => $v) {
            $keySum += $k;
        }
        return $keySum;
    });
```


>
> 💡
>
> You may also extend the `Dash` class to implement custom transformations.  
> For such uses the `Dash::$wrapperClass` static prop may be set, so that the `Dash::collect` function uses the extended class.
> 


## Using a global alias

If you desire a global alias to create a Dash-wrapped collection, such as `_dash`,
the best way is to register the global function in your bootstrap like so:
```php
use Dakujem\Toru\Dash;

if (!function_exists('_dash')) {
    function _dash(iterable $input): Dash {
        return Dash::collect($input);
    }
}
```

You can also place this function definition inside a file (e.g. `/bootstrap/dash.php`) that you automatically load using Composer.
In your `composer.json` file, add an autoloader rule as such:
```json
{
  "autoload": {
    "files": [
      "bootstrap/dash.php"
    ]
  }
}
```
You no longer need to import the `Dash` class.
```php
_dash($collection)->filter( /* ... */ )->map( /* ... */ )->toArray();
```



## Caveats

Generators, while being powerful, come with their own caveats:

1. working with keys (indexes) may be tricky
2. generators are not rewindable


### Generators and caveats with keys when casting to array

The combination of `chain` and `toArray` (or `iterator_to_array`) behaves like native `array_replace`:
```php
Itera::toArray(
    Itera::chain([1, 2], [3, 4])
);
```
The result will be `[3, 4]`, which might be unexpected. The reason is that the iterables (arrays in this case) have **overlapping keys**, and the later values overwrite the previous ones, _when casting to array_.
```php
Itera::toArray(
    Itera::chain([
        0 => 1, 
        1 => 2,
    ], [
        0 => 3,
        1 => 4,
    ])
);
```

This issue is not present when looping through the iterator:
```php
foreach(Itera::chain([1, 2], [3, 4]) as $key => $value){
    echo "$key: $value\n";
}
```
This will correctly output
```php
0:1
1:2
0:3
1:4
```

If we are not interested in the keys, then a solution is to use `toArrayValues`,
which is a shorthand for the chained call `Itera::toArray(Itera::valuesOnly( $input ))`.

Also note that generators may typically yield keys of _any_ type,
but when casting to arrays, only values usable as native array keys are permitted,
keys of other value types will trigger an error.


### Generators are not rewindable

Once an iteration of a generator is started, calling `rewind` on it will throw an error.
This issue may be overcome using the provided `Regenerator` iterator (read below).


## Supporting stuff

### Regenerator

`Regenerator` is a transparent wrapper for callables returning iterators, especially _generator objects_.
These may be directly _generator functions_, or callables wrapping them.

`Regenerator` enables **rewinding of generators**, which is not permitted in general.
The generators are not actually rewound, but are created again upon each rewinding.

Since most of the iteration primitives in this library are implemented using generators, this might be handy.

> Note: Rewinding happens automatically when iterating using `foreach`.

Let's illustrate it on an example.
```php
$generatorFunction = function(): iterable {
    yield 1;
    yield 2;
    yield 'foo' => 'bar';
    yield 42;
};

$generatorObject = $generatorFunction();

foreach ($generatorObject as $key => $val) { /* ... */ }
// subsequent iteration will throw an exception
foreach ($generatorObject as $key => $val) { /* ... */ } // Exception: Cannot rewind a generator that was already run  
```

This may be solved by calling the _generator function_ repeatedly for each iteration.
```php
// Instead of iterating over the same generator object, the generator function is called multiple times. Each call creates a new generator object.
foreach ($generatorFunction() as $key => $val) { /* ... */ }
// A new generator object is created with every call to $generatorFunction().
foreach ($generatorFunction() as $key => $val) { /* ... */ } // no exception this time
```

In most cases, that will be the solution, but sometimes an `iterable`/`Traversable` object is needed.

```php
use Dakujem\Toru\Dash;

$it = Dash::collect($generatorFunction);           // TypeError - not possible, the generator function is not iterable itself
$it = Dash::collect(fn() => $generatorFunction()); // TypeError - the argument must be iterable

$dash = Dash::collect($generatorFunction());       // works, but has the same drawback as described above
foreach ($dash->filter($filterFn) as $val) { /* ... */ }
foreach ($dash->filter($otherFilterFn) as $val) { /* ... */ } // fails
```

This is where `Regenerator` comes into play.

```php
use Dakujem\Toru\Dash;
use Dakujem\Toru\Regenerator;

$dash = new Regenerator(fn() => Dash::collect($generatorFunction()));
foreach ($dash->filter($filterFn) as $val) { /* ... */ }
foreach ($dash->filter($otherFilterFn) as $val) { /* ... */ } // works, hooray!
```

`Regenerator` internally calls the provider function whenever needed (i.e. whenever rewound),
while also implementing the `Traversable` interface.


### Pipeline

A simple processing pipeline implementation.
Useful with `IteraFn` class to compose processing algorithms.

```php
use Dakujem\Toru\IteraFn;
use Dakujem\Toru\Pipeline;

$alteredCollection = Pipeline::through(
    $collection,
    IteraFn::filter(predicate: $filterFunction),
    IteraFn::map(values: $mapper),
);
// Pipelines are not limited to producing iterable collections, they may produce any value types:
$average = Pipeline::through(
    $collection,
    IteraFn::filter(predicate: $filterFunction),
    IteraFn::reduce(reducer: fn($carry, $current) => $carry + $current, initial: 0),
    fn(int $sum) => $sum / $sizeOfCollection,
);
```


## Why iterables

Why bother with iterators when PHP has an extensive support for arrays?  
There are many cases where **iterators are more effective than arrays**. Usually when dealing with large (or possibly even infinite) collections.

The use-case scenario for iterators is comparable to stream resources.
You will know that stuffing uploaded files into a string variable is not the best idea all the time.
It will surely work with small files, try that with 4K video, though.

A good example might be a directory iterator.  
How many files might there be? Dozens? Millions? Stuffing that into an array may soon drain the memory reserves of your application.

So why use the `iterable` type hint instead of `array`?  
Simply to extend the possible use-cases of a function/method, where possible.


## Effectiveness

🚧 ... TODO
compare to arrays, merging.


## Alternatives

You might not need this library.
- `mpetrovich/dash` provides a full range of transformation functions, uses _arrays_ internally
- `lodash-php/lodash-php` imitates Lodash and provides a full range of utilities, uses _arrays_ internally
- `nikic/iter` implements a range of iteration primitives using _generators_, authored by a PHP core team member
- `illuminate/collections` should cover the needs of most Laravel developers, check the inner implementation for details
- in many cases, a `foreach` will do the same job

This library (`dakujem/toru`) does not provide a full range of ready-made transformation functions,
rather provides the most common ones and means to bring and compose own transformations.

It originally started as an alternative to `nikic/iter` for daily tasks, which to me has a somewhat cumbersome interface.  
The `Itera` static _class_ tries to fix that
by using a single class import instead of multiple function imports
and by reordering the parameters so that the input collection is the first one.  
Still, composing multiple operations into one transformation is cumbersome, so the `IteraFn` factory was implemented to fix that.
It worked well, but was still verbose for mundane tasks.  
To allow concise Lodash-style chained calls, the `Dash` class was designed.
With it, it's possible to compose transformations neatly.


## Contribution and future development

The intention is not to provide a plethora specific functions, rather offer tools for most used cases.

That being said, good quality PRs will be accepted.

**Possible additions** include:
 
- `combine` values and keys
- `zip` multiple iterables


---

🚧 ... TODO

effective
generators


`IteraFn` provides methods to create partially applied functions with the input collection being the free parameter, fixing the rest.
This then enables calls to `Pipeline::through()` to compose decoration pipelines usable in certain contexts.

For example, to create a pipeline that will

While in most cases a `foreach` will do, when dealing with collections of unknown size,
operations like `array_merge`, `array_flip`, `array_map`, `array_combine` will be memory hungry.
When you need to use those, instead of `iterator_to_array` you now have a tool to do it more effectively.

So when you encounter an iterator and need to do some transformations, you now have an effective tool to do it.

```php
use Dakujem\Toru\Itera;
use Dakujem\Toru\IteraFn;
use Dakujem\Toru\Pipeline;
use Dakujem\Toru\Dash;

$sequence = Itera::produce(fn() => rand()); // infinite iterator

// A naive foreach may be the best solution in certain cases...
$count = 0;
$array = [];
foreach ($sequence as $i) {
    if (0 == $i % 2) {
        $array[$i] = 'the value is ' . $i;
        if ($count >= 1000) {
            break;
        }
    }
}

// While the standalone static methods may be handy, they are not suited for complex computations.
$interim = Itera::filter($sequence, fn($i) => 0 == $i % 2);
$interim = Itera::reindex($interim, fn($i) => $i);
$interim = Itera::apply($interim, fn($i) => 'the value is ' . $i);
$interim = Itera::limit($interim, 1000);
$array = Itera::toArray($interim);

// or without the interim variable...
$array = Itera::toArray(
    Itera::limit(
        Itera::apply(
            Itera::reindex(
                Itera::filter(
                    $sequence,
                    fn($i) => 0 == $i % 2,
                ),
                fn($i) => $i,
            ),
            fn($i) => 'the value is ' . $i,
        ),
        1000,
    )
);

// Complex pipelines may be composed using partially applied callables.
$array = Pipeline::through(
    $sequence,
    IteraFn::filter(fn($i) => 0 == $i % 2),
    IteraFn::reindex(fn($i) => $i),
    IteraFn::apply(fn($i) => 'the value is ' . $i),
    IteraFn::limit(1000),
    IteraFn::toArray(),
);

// Lodash-style fluent notation.
$array = Dash::collect($sequence)
    ->filter(fn($i) => 0 == $i % 2)
    ->reindex(fn($i) => $i)
    ->map(fn($i) => 'the value is ' . $i)
    ->limit(1000)
    ->toArray();
```


### Example: Listing images in a directory

Let us solve a simple task: List all images of a directory recursively.

You may have generative AI do this too, or come up with something like this:
```php
$images = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
/** @var SplFileInfo $fileInfo */
foreach ($iterator as $fileInfo) {
    // Skip directories
    if ($fileInfo->isDir()) {
        continue;
    }
    // Get the full path of the file
    $filePath = $fileInfo->getPathname();
    // Reject non-image files (hacky)
    if (!@getimagesize($filePath)) {
        continue;
    }
    $images[$filePath] = $fileInfo;
}
```

This will work in development, but will have a huge impact on your server if you try to list _millions_ of images,
something not uncommon for mid-sized content-oriented projects.

The way to fix that is by utilizing a generator:
```php
$listImages = function(string $dir): Generator {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    /** @var SplFileInfo $fileInfo */
    foreach ($iterator as $fileInfo) {
        // Skip directories
        if ($fileInfo->isDir()) {
            continue;
        }
        // Get the full path of the file
        $filePath = $fileInfo->getPathname();
        // Reject non-image files (hacky)
        if (!@getimagesize($fileInfo->getPathname())) {
            continue;
        }
        yield $filePath => $fileInfo;
    }
};
$images = $listImages($dir);
```

And what if you could create equivalent generator like this...
```php
$images =
  Dash::wrap(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)))  // recursively iterate over a dir
    ->filter(fn(SplFileInfo $fileInfo) => !$fileInfo->isDir())                     // reject directories
    ->filter(fn(SplFileInfo $fileInfo) => @getimagesize($fileInfo->getPathname())) // accept only images (hacky)
    ->reindex(fn(SplFileInfo $fileInfo) => $fileInfo->getPathname());              // index by the full path of the file
```

It now depends on personal preference. Both will do the trick and be equally efficient.

