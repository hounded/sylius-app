<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\FixerRunner;

use SebastianBergmann\Diff\Differ;

final class ChangedLinesDetector
{
    /**
     * @var Differ
     */
    private $differ;

    public function __construct(Differ $differ)
    {
        $this->differ = $differ;
    }

    /**
     * @return int[]
     */
    public function detectInBeforeAfter(string $oldContent, string $newContent): array
    {
        $changedLines = [];
        $currentLine = 1;

        $diffTokens = $this->differ->diffToArray($oldContent, $newContent);

        for ($i = 0; $i < count($diffTokens); ++$i) {
            $diffToken = $diffTokens[$i];

            if ($diffToken[1] === 2) { // line was removed
                $changedLines[] = $currentLine;
                if (! isset($diffTokens[$i + 1])) {
                    continue;
                }

                if ($diffTokens[$i + 1][1] === 1) { // next line was added
                    ++$i; // do not record it twice, skip next $diffToken
                    ++$currentLine;

                    continue;
                }
            } elseif ($diffToken[1] === 1) { // line was added
                $changedLines[] = $currentLine;
            }

            ++$currentLine;
        }

        return $changedLines;
    }
}
