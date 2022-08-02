<?php

namespace vaersaagod\linkmate\utilities;

use Exception;
use Throwable;

use yii\helpers\ArrayHelper;

/**
 * Class ElementSourceValidator
 */
class ElementSourceValidator
{
    /**
     * @var array
     */
    private array $availableSources;

    /**
     * @var ElementSourceValidator[]
     */
    private static array $validators = [];


    /**
     * ElementSourceValidator constructor.
     *
     * @param string $elementType The fully qualified element class path, e.g. craft\\elements\\Entry
     *
     * @throws Exception
     */
    public function __construct(string $elementType)
    {
        $idPath = self::getElementIdPath($elementType);
        if (is_null($idPath)) {
            throw new Exception('Unsupported element type: ' . (string)$elementType);
        }

        $availableSources = [];
        foreach ($elementType::sources('index') as $source) {
            if (!array_key_exists('key', $source)) {
                continue;
            }

            $id = ArrayHelper::getValue($source, $idPath);
            if (is_null($id)) {
                continue;
            }

            $availableSources[] = [
                'key' => $source['key'],
                'id' => $id,
            ];
        }

        $this->availableSources = $availableSources;
    }

    /**
     * @param array $originalSources
     *
     * @return array
     */
    public function validate(array $originalSources): array
    {
        $resolvedSources = [];

        foreach ($originalSources as $originalSource) {
            $resolvedSource = $this->validateSource($originalSource);
            if (!is_null($resolvedSource)) {
                $resolvedSources[] = $resolvedSource;
            }
        }

        return $resolvedSources;
    }

    /**
     * @param string $originalSource
     *
     * @return null|string
     */
    private function validateSource(string $originalSource): ?string
    {
        $maybeSource = null;

        // Fetch id from source. If we don't find one, this is not referring
        // to an actual source (e.g. `*`) so leave it untouched.
        $originalId = self::getIdFromSource($originalSource);
        if (is_null($originalId)) {
            return $originalSource;
        }

        // Check all sources
        foreach ($this->availableSources as $availableSource) {
            // Perfect key match, just resolve
            if ($availableSource['key'] == $originalSource) {
                return $originalSource;
            }

            // Check for section id match
            if ($availableSource['id'] == $originalId) {
                $maybeSource = $availableSource;
            }
        }

        // Did not find a perfect match, return the maybe hit
        return is_null($maybeSource)
            ? null
            : $maybeSource['key'];
    }

    /**
     * @param string $elementType The fully qualified element class path, e.g. craft\\elements\\Entry
     * @param array $sources
     *
     * @return array
     */
    public static function apply(string $elementType, array $sources): array
    {
        try {
            if (!array_key_exists($elementType, self::$validators)) {
                self::$validators[(string)$elementType] = new ElementSourceValidator($elementType);
            }

            return self::$validators[(string)$elementType]->validate($sources);
        } catch (Throwable) {

        }

        return $sources;
    }

    /**
     * @param string $elementType The fully qualified element class path, e.g. craft\\elements\\Entry
     *
     * @return array|null
     */
    public static function getElementIdPath(string $elementType): ?array
    {
        return match ($elementType) {
            'craft\\elements\\Asset' => ['criteria', 'folderId'],
            'craft\\elements\\Category' => ['criteria', 'groupId'],
            'craft\\elements\\Entry' => ['criteria', 'sectionId'],
            default => null,
        };
    }

    /**
     * @param string $originalSource
     *
     * @return null|string
     */
    public static function getIdFromSource(string $originalSource): ?string
    {
        $idOffset = strpos($originalSource, ':');
        if ($idOffset === false) {
            return null;
        }

        return substr($originalSource, $idOffset + 1);
    }
}
