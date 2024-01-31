<?php

namespace PhpStats\Commands;

use Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'stats')]
class Stats extends Command
{
    const string REGEX_PHP_FILES = '/^.+\.php$/i';
    const string ARG_PATH = 'path';

    protected function configure(): void
    {
        $this
            ->addArgument(name: self::ARG_PATH, mode: InputArgument::REQUIRED, description: 'Path to files you want to analyze.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $files = self::rsearch($input->getArgument(self::ARG_PATH), self::REGEX_PHP_FILES);

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $visitor = new \PhpStats\Visitor();
        $traverser = new NodeTraverser($visitor);

        $progressBar = $io->createProgressBar(count($files));
        $step = 0;
        foreach ($files as $file) {
            $progressBar->advance();
            try {
                $ast = $parser->parse(file_get_contents($file));
                $traverser->traverse($ast);

                $foundNodes = $visitor->getFoundNodes();
                if (!empty($foundNodes)) {
//                    var_dump($visitor->stats);
//                    $io->listing($visitor->stats);
//                    foreach ($foundNodes as $node) {
//                        $io->writeln($file . ':' .$node->getLine());
//                    }
                }
            } catch (Error $error) {
                $io->error("Error parsing file '{$file}': {$error->getMessage()}");
            }
        }
        $progressBar->finish();
        $io->newLine(2);
        $io->table(['Features found'], array_map(fn($s) => [$s], array_keys($visitor->stats)));

        return Command::SUCCESS;
    }

    public static function rsearch($folder, $regex) {
        $directoryIterator = new RecursiveDirectoryIterator($folder);
        $iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
        $regexIterator = new RegexIterator($iteratorIterator, $regex, RegexIterator::GET_MATCH);

        return array_keys(iterator_to_array($regexIterator));
    }
}
