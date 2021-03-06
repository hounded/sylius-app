<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Namespaces;

use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\ReferencedName;
use SlevomatCodingStandard\Helpers\ReferencedNameHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatement;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

class UnusedUsesSniff implements \PHP_CodeSniffer\Sniffs\Sniff
{

	public const CODE_UNUSED_USE = 'UnusedUse';
	public const CODE_MISMATCHING_CASE = 'MismatchingCaseSensitivity';

	/** @var bool */
	public $searchAnnotations = false;

	/**
	 * @return mixed[]
	 */
	public function register(): array
	{
		return [
			T_OPEN_TAG,
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $openTagPointer
	 */
	public function process(\PHP_CodeSniffer\Files\File $phpcsFile, $openTagPointer): void
	{
		$unusedNames = UseStatementHelper::getUseStatements($phpcsFile, $openTagPointer);
		$referencedNames = ReferencedNameHelper::getAllReferencedNames($phpcsFile, $openTagPointer);

		$usedNames = [];
		foreach ($referencedNames as $referencedName) {
			$name = $referencedName->getNameAsReferencedInFile();
			$pointer = $referencedName->getStartPointer();
			$nameParts = NamespaceHelper::getNameParts($name);
			$nameAsReferencedInFile = $nameParts[0];
			$nameReferencedWithoutSubNamespace = count($nameParts) === 1;
			$uniqueId = $nameReferencedWithoutSubNamespace
				? UseStatement::getUniqueId($referencedName->getType(), $nameAsReferencedInFile)
				: UseStatement::getUniqueId(ReferencedName::TYPE_DEFAULT, $nameAsReferencedInFile);
			if (
				NamespaceHelper::isFullyQualifiedName($name)
				|| !isset($unusedNames[$uniqueId])
			) {
				continue;
			}

			if ($nameReferencedWithoutSubNamespace && !$referencedName->hasSameUseStatementType($unusedNames[$uniqueId])) {
				continue;
			}
			if ($unusedNames[$uniqueId]->getNameAsReferencedInFile() !== $nameAsReferencedInFile) {
				$phpcsFile->addError(sprintf(
					'Case of reference name "%s" and use statement "%s" do not match.',
					$nameAsReferencedInFile,
					$unusedNames[$uniqueId]->getNameAsReferencedInFile()
				), $pointer, self::CODE_MISMATCHING_CASE);
			}

			$usedNames[$uniqueId] = true;
		}

		if ($this->searchAnnotations) {
			$tokens = $phpcsFile->getTokens();
			$searchAnnotationsPointer = $openTagPointer + 1;
			while (true) {
				$docCommentOpenPointer = TokenHelper::findNext($phpcsFile, T_DOC_COMMENT_OPEN_TAG, $searchAnnotationsPointer);
				if ($docCommentOpenPointer === null) {
					break;
				}

				$annotations = AnnotationHelper::getAnnotations($phpcsFile, $docCommentOpenPointer);

				if (count($annotations) === 0) {
					$searchAnnotationsPointer = $tokens[$docCommentOpenPointer]['comment_closer'] + 1;
					continue;
				}

				foreach ($unusedNames as $useStatement) {
					$nameAsReferencedInFile = $useStatement->getNameAsReferencedInFile();
					$uniqueId = UseStatement::getUniqueId($useStatement->getType(), $nameAsReferencedInFile);

					foreach ($annotations as $annotationName => $annotationsByName) {
						if (preg_match('~^@(' . preg_quote($nameAsReferencedInFile, '~') . ')(?=[^a-z\\d]|$)~i', $annotationName, $matches)) {
							$usedNames[$uniqueId] = true;

							if ($matches[1] !== $nameAsReferencedInFile) {
								/** @var \SlevomatCodingStandard\Helpers\Annotation $annotation */
								foreach ($annotationsByName as $annotation) {
									$phpcsFile->addError(sprintf(
										'Case of reference name "%s" and use statement "%s" do not match.',
										$matches[1],
										$unusedNames[$uniqueId]->getNameAsReferencedInFile()
									), $annotation->getPointer(), self::CODE_MISMATCHING_CASE);
								}
							}
						}

						/** @var \SlevomatCodingStandard\Helpers\Annotation $annotation */
						foreach ($annotationsByName as $annotation) {
							if ($annotation->getParameters() === null) {
								continue;
							}

							if (
								!preg_match('~(?<=^|[^a-z\s\\\\])(' . preg_quote($nameAsReferencedInFile, '~') . ')(?=::)~i', $annotation->getParameters(), $matches)
								&& !preg_match('~(?<=@)(' . preg_quote($nameAsReferencedInFile, '~') . ')(?=\\\\|\()~i', $annotation->getParameters(), $matches)
							) {
								continue;
							}

							$usedNames[$uniqueId] = true;

							if ($matches[1] === $nameAsReferencedInFile) {
								continue;
							}

							$phpcsFile->addError(sprintf(
								'Case of reference name "%s" and use statement "%s" do not match.',
								$matches[1],
								$unusedNames[$uniqueId]->getNameAsReferencedInFile()
							), $annotation->getPointer(), self::CODE_MISMATCHING_CASE);
						}

						/** @var \SlevomatCodingStandard\Helpers\Annotation $annotation */
						foreach ($annotationsByName as $annotation) {
							if ($annotation->getContent() === null) {
								continue;
							}

							if (!preg_match('~(?:^|\||\$\\w+\\s+)(' . preg_quote($nameAsReferencedInFile, '~') . ')(?=\\s|\\\\|\||\[|$)~i', $annotation->getContent(), $matches)) {
								continue;
							}

							$usedNames[$uniqueId] = true;

							if ($matches[1] === $nameAsReferencedInFile) {
								continue;
							}

							$phpcsFile->addError(sprintf(
								'Case of reference name "%s" and use statement "%s" do not match.',
								$matches[1],
								$unusedNames[$uniqueId]->getNameAsReferencedInFile()
							), $annotation->getPointer(), self::CODE_MISMATCHING_CASE);
						}
					}
				}

				$searchAnnotationsPointer = $tokens[$docCommentOpenPointer]['comment_closer'] + 1;
			}
		}

		foreach (array_diff_key($unusedNames, $usedNames) as $unusedUse) {
			$fullName = $unusedUse->getFullyQualifiedTypeName();
			if ($unusedUse->getNameAsReferencedInFile() !== $fullName && $unusedUse->getNameAsReferencedInFile() !== NamespaceHelper::getUnqualifiedNameFromFullyQualifiedName($fullName)) {
				$fullName .= sprintf(' (as %s)', $unusedUse->getNameAsReferencedInFile());
			}
			$fix = $phpcsFile->addFixableError(sprintf(
				'Type %s is not used in this file.',
				$fullName
			), $unusedUse->getPointer(), self::CODE_UNUSED_USE);
			if (!$fix) {
				continue;
			}

			$phpcsFile->fixer->beginChangeset();
			$endPointer = TokenHelper::findNext($phpcsFile, T_SEMICOLON, $unusedUse->getPointer()) + 1;
			for ($i = $unusedUse->getPointer(); $i <= $endPointer; $i++) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
			$phpcsFile->fixer->endChangeset();
		}
	}

}
