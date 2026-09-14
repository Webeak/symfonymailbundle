<?php
// Only peripheral private Webeak dependencies are replaced. The tests run the
// real mail bundle, Symfony dispatcher, Swift message and Doctrine ORM/DBAL.
namespace Webeak\Bundle\DoctrineExtensionsBundle\Entity {
    abstract class AbstractBasicEntity { protected $id; }
}
namespace Webeak\Bundle\EssentialBundle\Exception {
    class RuntimeException extends \RuntimeException {}
    class InvalidArgumentException extends \InvalidArgumentException {}
}
namespace Webeak\Bundle\EssentialBundle {
    class StaticLogger {
        public static function critical($message, array $context = []) {}
    }
}
namespace Webeak\Component\Utils {
    class ArrayUtils {
        public static function mergeRecursiveDistinct(array $first, array $second) { return array_replace_recursive($first, $second); }
        public static function ensureArray($value) { return is_array($value) ? $value : ($value === null ? [] : [$value]); }
        public static function getValue(array $values, $key, $default = null) { return $values[$key] ?? $default; }
    }
}
