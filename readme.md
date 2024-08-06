# Toru 取る

[![Test Suite](https://github.com/dakujem/toru/actions/workflows/php-test.yml/badge.svg)](https://github.com/dakujem/toru/actions/workflows/php-test.yml)
[![Coverage Status](https://coveralls.io/repos/github/dakujem/toru/badge.svg?branch=trunk)](https://coveralls.io/github/dakujem/toru?branch=trunk)

Toru is a standalone tool for _iterable_ collections for simple day-to-day tasks and advanced optimizations.  
Most of its functionality is based on native _generators_ for efficiency with large data sets.

>
> 💿 `composer require dakujem/toru`
>

The package name comes from Japanese word "toru" (取る), which may mean "to take", "to pick up" or even "to collect".

Toru provides a few common **iteration primitives** (e.g. `map`, `filter`, `tap`),
**aggregates** (e.g. `reduce`, `search`, `count`)
and utility functions (e.g. `chain`) implemented using generators or efficient iterations.

Also implements **Lodash-style fluent wrapper** to simplify composition of various transformations on iterable collections.

The aim of Toru is to provide simple tools to work with the native `iterable` type*.  
Leveraging generators, Toru enables memory-efficient operations on large datasets.

All callable parameters (_mapper_, _predicate_, _reducer_ and _effect_ functions)
always **receive keys** along with values.  
This is a key advantage over native functions like `array_map`, `array_reduce`, `array_walk`, or `array_filter`.

Use Toru when:

- in need to perform operations on `iterable` without converting to arrays
- in need to work with _keys_, as alternative to `array_map`, `array_filter` or `array_reduce`
- unable to use `foreach`
- working with large datasets
- running out of memory when transforming large collections (using arrays)
- wanting to compose collection transformations neatly in fluent Lodash-like style
- in need of lazy evaluation (on-demand, per-element)

>
> \* The `iterable` is a built-in compile time type alias for `array|Traversable` encompassing all arrays and iterators,
> so it's not exactly a native type, technically speaking.
>


## Examples

Task: Iterate over multiple large arrays (or other iterable collections) with _low memory footprint_:
```php
use Dakujem\Toru\Itera;

// No memory wasted on creating a compound array. Especially true when the arrays are huge.
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

Task: Create a list of all files in a directory as `path => FileInfo` pairs without risk of running out of memory:
```php
$files = _dash(
        new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir))
    )                                                                   // recursively iterate over a dir
    ->filter(fn(\SplFileInfo $fileInfo) => !$fileInfo->isDir())         // reject directories
    ->reindex(fn(\SplFileInfo $fileInfo) => $fileInfo->getPathname());  // index by full file path
```
Note that here we use global function `_dash`, which you may optionally define in your project.
See the "Using a global alias" section below.


## Usage

Most of the primitives described in API section below are implemented in **3 forms**:
1. as a static method `Itera::*(iterable $input, ...$args)`,
   for simple cases
2. as a fluent method of the `Dash` wrapper, `Dash::*(...$args): Dash`,
   best suited for fluent composition
3. as a factory method that creates partially applied callables `IteraFn::*(...$args): callable`,
   to be composed into pipelines or used as filters (i.e. in Twig, Blade, Latte, ...)


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

// `append` is only present in `Dash` and `IteraFn` classes as an alias to `chain`
Dash::append(iterable ...$more): Dash
IteraFn::append(iterable ...$more): callable
```

The `chain` method creates an iterable composed of all the arguments.  
The resulting iterable will yield all values (preserving keys) from the first iterable, then the next, then the next, and so on.

Compared to `array_replace` (or `array_merge` or the union operator `+` on arrays) this is very _memory efficient_,
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

> This is an aggregate function, it will immediately consume the input.

Similar to `array_reduce`, but works with any _iterable_ and passes keys to the reducer.
```php
use Dakujem\Toru\Itera;

Itera::reduce(iterable $input, callable $reducer, mixed $initial): mixed
```

The reducer signature is
```php
fn(mixed $carry, mixed $value, mixed $key): iterable|mixed
```

When using the `Dash::reduce` fluent call, the result is treated in two different ways:
1. when an `iterable` value is returned, the result is wrapped into a new `Dash` instance to allow to continue the fluent call chain (useful for matrix reductions)
2. when other `mixed` value type is returned, the result is returned as-is

```php
use Dakujem\Toru\Dash;

// The value is returned directly, because it is not iterable:
Dash::collect([1,2,3])->reduce(fn() => 42); // 42

// The value `[42]` is iterable, thus a new `Dash` instance is returned:
Dash::collect([1,2,3])->reduce(fn() => [42])->count(); // 1
```


### Filtering: `filter`

Create a generator that yields only the items of the input collection that the predicate returns _truthy_ for.

```php
use Dakujem\Toru\Itera;

Itera::filter(iterable $input, callable $predicate): iterable
```

Accept and eliminate elements based on a callable predicate.  
When the predicate returns _truthy_, the element is accepted and yielded.  
When the predicate returns _falsy_, the element is rejected and skipped.

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

> These are aggregate functions, they will immediately consume the input.

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

> Note that when omitting, the selected number of elements (`$omit`/`$offset`)
> is still iterated over but not yielded.

Similar to `array_slice`, preserving the keys.

> Note:  
> Unlike `array_slice`, the keys are always preserved.
> Use `Itera::valuesOnly` when dropping the keys is desired.


### Alterations: `valuesOnly`, `keysOnly`, `flip`

Create a generator that will only yield values, keys, or will flip them.

```php
use Dakujem\Toru\Itera;

Itera::valuesOnly(iterable $input): iterable
Itera::keysOnly(iterable $input): iterable
Itera::flip(iterable $input): iterable
```

The `flip` function is similar to `array_flip`,  
the `valuesOnly` function is similar to `array_values`,  
and the `keysOnly` function is similar to `array_keys`.

```php
use Dakujem\Toru\Itera;

Itera::valuesOnly(['a' => 'Adam', 'b' => 'Betty']); // ['Adam', 'Betty']
Itera::keysOnly(['a' => 'Adam', 'b' => 'Betty']);   // ['a', 'b']
Itera::flip(['a' => 'Adam', 'b' => 'Betty']);       // ['Adam' => 'a', 'Betty' => 'b']
```


### Conversions: `toArray`, `toArrayValues`, `toArrayMerge`, `toIterator`, `ensureTraversable`

> These functions immediately use the input.

Convert the input to `array`/`Iterator` from generic `iterable`.

```php
use Dakujem\Toru\Itera;

Itera::toArray(iterable $input): array
Itera::toArrayMerge(iterable $input): array
Itera::toArrayValues(iterable $input): array
Itera::toIterator(iterable $input): \Iterator
Itera::ensureTraversable(iterable $input): \Traversable
```

> 💡
>
> Iterators in general, Generators specifically, impose a challenge when being cast to arrays.
> Read the "Caveats" section below.
> 
> ```php
> Itera::toArray(Itera::chain([1,2], [3,4])); // --> [3,4] ❗
> ```

There are 3 variants of the "to array" operation.

| Toru function   | Behaves like    | Associative keys | Numeric keys  | Values overwritten when keys overlap   | 
|:----------------|:----------------|:-----------------|:--------------|:---------------------------------------|
| `toArray`       | `array_replace` | preserved        | preserved     | values with **any overlapping keys** ❗ |
| `toArrayMerge`  | `array_merge`   | preserved        | **discarded** | only values with _associative_ keys    |
| `toArrayValues` | `array_values`  | **discarded**    | **discarded** | no values are overwritten              |


### Tapping: `tap`, `each`

Create a generator, that will call an effect function for each element upon iteration.
```php
use Dakujem\Toru\Itera;

Itera::tap(iterable $input, callable $effect): iterable
Itera::each(iterable $input, callable $effect): iterable  // alias for `tap`
```

The signature of the effect function is
```php
fn(mixed $value, mixed $key): void
```
The return values are discarded.


### Repeating: `repeat`, `loop`, `replicate`

```php
use Dakujem\Toru\Itera;

Itera::repeat(mixed $input): iterable
Itera::loop(iterable $input): iterable
Itera::replicate(iterable $input, int $times): iterable
```

The `repeat` function repeats the input as-is, indefinitely.  
The `loop` function yields individual elements of the input, indefinitely.  
The `replicate` function yields individual elements of the input, exactly specified number of times.

Both `repeat` and `loop` should be wrapped into a `limit` and `valuesOnly` if cast to arrays.

Please note that if the `loop` and `replicate` functions have a generator on the input,
they may/will run into the issues native to generators - being non-rewindable and having overlapping indexes.


### Producing: `make`, `produce`

The `produce` function will create an infinite generator that will call the provided producer function upon each iteration.
```php
use Dakujem\Toru\Itera;

Itera::produce(callable $producer): iterable
```

It is supposed to be used with the `limit` function.
```php
use Dakujem\Toru\Itera;

Itera::limit(Itera::produce(fn() => rand()), 1000); // a sequence of 1000 pseudorandom numbers
Itera::produce(fn() => 42); // an infinite sequence of the answer to life, the universe, and everything
Itera::produce(fn(int $i) => $i); // an infinite sequence of integers 0, 1, 2, 3, ...
```

The `make` function creates an iterable collection from its arguments.
It is only useful in scenarios, where an iterator (a generator) is needed. Use arrays otherwise.

These two functions are only available as static `Itera` methods.

To produce both keys and values, one might use `unfold` to wrap the `produce` which would return key=>value pairs.
```php
use Dakujem\Toru\Itera;

Itera::unfold(
    Itera::produce(fn(int $i) => [ calculateKey($i) => calculateValue($i) ])
);
```


## Lazy evaluation

Generator functions are lazy by nature.  
Invoking a generator function creates a generator object, but does not execute any code.  
The code is executed once an iteration starts (e.g. via `foreach`).

By passing a generator as an input to another generator function, that generator is _decorated_ and a new one is returned.
This decoration is still lazy and no code execution occurs just yet.

```php
use Dakujem\Toru\Dash;
use Dakujem\Toru\Itera;

// Create a generator from an input collection.
$collection = Itera::apply(input: Itera::filter(input: $input, filter: $filter), values: $mapper);
// or using Dash
$collection = Dash::collect($input)->filter(filter: $filter)->apply(values: $mapper);


// No generator code has been executed so far.
// The evaluation of $filter and $mapper callables begins with the first iteration below.
foreach($collection as $key => $value) {
    // Only at this point the mapper and filter functions are executed,
    // once per element of the input collection.
    // The generator execution is then _paused_ until the next iteration.
}
```

> 💡  
> If such an iteration was terminated before the whole collection had been iterated over (e.g. via `break`),
> the callables would NOT be called for the remaining elements.  
> This increases efficiency in cases, where it is unsure how many elements of a collection will actually be consumed.

Every function provided by Toru that returns `iterable` uses **generators** and is lazy.  
Examples: `adjust`, `map`, `chain`, `filter`, `flip`, `tap`, `slice`, `repeat`

Other functions, usually returning `mixed` or scalar values, are **aggregates**
and cause immediate iteration and generator code execution, exhausting generators on the input.  
Examples: `reduce`, `count`, `search`, `toArray`, `firstValue`


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

$collection = Dash::collect($array)->map($mapper)->filter($predicate);
```

With `array_reduce` this is even more convoluted, because there is no way to pass the keys to the native function.  
One way to deal with it is to transform the array values to include the indexes
and to alter the reducer to account for the changed data type.
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

> Take care when defining global function `_` or `__` as it may interfere with other functions
> (e.g. Gettext extension) or common i8n function alias.


## Caveats

Generators, while being powerful, come with their own caveats:

1. working with keys (indexes) may be tricky
2. generators are not rewindable

Please understand generators before using Toru, it may help avoid a headache:  
📖 [Generators overview](https://www.php.net/manual/en/language.generators.overview.php)  
📖 [Generator syntax](https://www.php.net/manual/en/language.generators.syntax.php)  


### Generators and caveats with keys when casting to array

There are two challenges native to generators when casting to arrays:
1. overlapping keys (indexes)
2. key types

**Overlapping keys** cause values to be overwritten when using `iterator_to_array`.  
And since generators may yield **keys of any type**, using them as array keys may result in `TypeError` exception.

The combination of `chain` and `toArray` (or `iterator_to_array`) behaves like native `array_replace`:
```php
use Dakujem\Toru\Itera;

Itera::toArray(
    Itera::chain([1, 2], [3, 4])
);
```
The result will be `[3, 4]`, which might be unexpected. The reason is that the iterables (arrays in this case) have overlapping keys,
and the later values overwrite the previous ones, when casting to array.
```php
use Dakujem\Toru\Itera;

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
use Dakujem\Toru\Itera;

foreach(Itera::chain([1, 2], [3, 4]) as $key => $value){
    echo "$key: $value\n";
}
```
The above will correctly output
```php
0:1
1:2
0:3
1:4
```

> See this code in action: [generator key collision](https://3v4l.org/huq9M)

If we are able to discard the keys, then the fastest solution is to use `toArrayValues`,
which is a shorthand for the chained call `Itera::toArray(Itera::valuesOnly( $input ))`.

If we wanted to emulate the behaviour of `array_merge`, Toru provides `toArrayMerge` function.  
This variant preserves the associative keys while discarding the numeric keys.

```php
use Dakujem\Toru\Itera;

Itera::toArrayMerge(
    Itera::chain([
        0 => 1,
        1 => 2,
        'foo' => 'bar',
    ], [
        0 => 3,
        1 => 4,
    ])
);
```
That call will produce the following array:
```php
[
     0 => 1,
     1 => 2,
     'foo' => 'bar',
     2 => 3,
     3 => 4,
]
```

> 💡
> 
> Note that generators may typically yield keys of _any_ type,
> but when casting to arrays, only values usable as native array keys are permitted,
> for keys of other value types, a `TypeError` will be thrown.


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
// Exception: Cannot rewind a generator that was already run
foreach ($generatorObject as $key => $val) { /* ... */ }
```

This may be solved by calling the _generator function_ repeatedly for each iteration.
```php
// Instead of iterating over the same generator object, the generator function
// is called multiple times. Each call creates a new generator object.
foreach ($generatorFunction() as $key => $val) { /* ... */ }
// A new generator object is created with every call to $generatorFunction().
foreach ($generatorFunction() as $key => $val) { /* ... */ } // ✅ no exception
```

In most cases, that will be the solution, but sometimes an `iterable`/`Traversable` object is needed.

```php
use Dakujem\Toru\Dash;

// Not possible, the generator function is not iterable itself
$it = Dash::collect($generatorFunction);           // TypeError

// Not possible, the argument to `collect` must be iterable
$it = Dash::collect(fn() => $generatorFunction()); // TypeError

// The correct way is to wrap the generator returned by the call,
// but it has the same drawback as described above
$dash = Dash::collect($generatorFunction());       
foreach ($dash->filter($filterFn) as $val) { /* ... */ }
// Exception: Cannot rewind a generator that was already run
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


### Storing intermediate value

Since most calls to Toru functions return generators, storing the intermediate value in a variable suffers the same issue.
```php
use Dakujem\Toru\Itera;

$filtered = Itera::filter($input, $predicate);
$mapped = Itera::apply($filtered, $mapper);

foreach($mapped as $k => $v) { /* ...*/ }

// Exception: Cannot rewind a generator that was already run
foreach($filtered as $k => $v) { /* ...*/ }
```

Again, the solution might be to create a function, like this:
```php
use Dakujem\Toru\Itera;

$filtered = fn() => Itera::filter($input, $predicate);
$mapped = fn() => Itera::apply($filtered(), $mapper);

foreach($mapped() as $k => $v) { /* ...*/ }

// this will work, the issue is mitigated by iterating over a new generator
foreach($filtered() as $k => $v) { /* ...*/ }
```

Alternatively, the `Regenerator` class comes handy.
```php
use Dakujem\Toru\Itera;
use Dakujem\Toru\Regenerator;

$filtered = new Regenerator(fn() => Itera::filter($input, $predicate));
$mapped = new Regenerator(fn() => Itera::apply($filtered(), $mapper));

foreach($mapped as $k => $v) { /* ...*/ }

// In this case, the regenerator handles function calls.
foreach($filtered as $k => $v) { /* ...*/ }
```


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
There are many cases where **iterators may be more efficient than arrays**.
Usually when dealing with large (or possibly even infinite) collections.

The use-case scenario for iterators is comparable to _stream_ resources.
You will know that stuffing uploaded files into a string variable is not the best idea all the time.
It will surely work with small files, try that with 4K video, though.

A good example might be a directory iterator.  
How many files might there be? Dozens? Millions? Stuffing that into an array may soon drain the memory reserves of your application.

So why use the `iterable` type hint instead of `array`?  
Simply to extend the possible use-cases of a function/method, where possible.


## Memory efficiency

The efficiency of generators stems from the fact that **no extra memory needs to be allocated**
when doing stuff like chaining multiple collections, filtering, mapping and so on. 

On the other hand, a `foreach` block will always execute faster, because there are no extra function calls involved.
Depending on your use case, the performance difference may be negligible, though.

However, in cloud environments, memory may be expensive. It is a tradeoff.

> For example, chaining multiple collections into one instead of using `array_merge` will be more efficient.
>
> https://3v4l.org/Ymksm  
> https://3v4l.org/OmUb3  
> https://3v4l.org/HMasj  


## Alternatives

You might not need this library.
- `mpetrovich/dash` provides a full range of transformation functions, uses _arrays_ internally
- `lodash-php/lodash-php` imitates Lodash and provides a full range of utilities, uses _arrays_ internally
- `nikic/iter` implements a range of iteration primitives using _generators_, authored by a PHP core team member
- `illuminate/collections` should cover the needs of most Laravel developers, check the inner implementation for details
- in many cases, a `foreach` will do the same job

This library (`dakujem/toru`) does not provide a full range of ready-made transformation functions,
rather provides the most common ones and means to bring in and compose own transformations.

It originally started as an alternative to `nikic/iter` for daily tasks, which to me has a somewhat cumbersome interface.  
The `Itera` static _class_ tries to fix that
by using a single class import instead of multiple function imports
and by reordering the parameters so that the input collection is consistently the first one.  
Still, composing multiple operations into one transformation is cumbersome, so the `IteraFn` factory was implemented to fix that.
It worked well, but was still verbose for mundane tasks.  
To allow concise Lodash-style chained calls, the `Dash` class was designed.
With it, it's possible to compose transformations neatly.


## Contribution and future development

The intention is not to provide a plethora specific functions, rather offer tools for most used cases.

That being said, good quality PRs will be accepted.

Possible additions may include:
 
- `combine` values and keys
- `zip` multiple iterables


---

## Appendix: Example code with annotations

### Illustration of various approaches

Observe the code below to see `foreach` and `Dash` solve a simple problem.
See when and why `Dash` may be more appropriate than `Itera` alone.

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

// While the standalone static methods may be handy,
// they are not suited for complex computations.
$interim = Itera::filter($sequence, fn($i) => 0 == $i % 2);
$interim = Itera::reindex($interim, fn($i) => $i);
$interim = Itera::apply($interim, fn($i) => 'the value is ' . $i);
$interim = Itera::limit($interim, 1000);
$array = Itera::toArray($interim);

// Without the interim variable(s), the reading order of the calls is reversed
// and the whole computation is not exactly legible.
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
$images = _dash(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir))) // recursively iterate over a dir
    ->filter(fn(SplFileInfo $fileInfo) => !$fileInfo->isDir())                       // reject directories
    ->filter(fn(SplFileInfo $fileInfo) => @getimagesize($fileInfo->getPathname()))   // accept only images (hacky)
    ->reindex(fn(SplFileInfo $fileInfo) => $fileInfo->getPathname());                // key by the full file path
```

It now depends on personal preference. Both will do the trick and be equally efficient.

