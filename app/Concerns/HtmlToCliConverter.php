<?php

namespace App\Concerns;

use Illuminate\Console\Command;

class HtmlToCliConverter
{
    private $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function convert($html)
    {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Parse HTML into DOM
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

        $this->processNode($dom->documentElement);
    }

    private function processNode($node, $indent = 0)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent);
            if (!empty($text)) {
                $this->command->line(str_repeat('  ', $indent) . $text);
            }
            return;
        }

        foreach ($node->childNodes as $child) {
            switch ($child->nodeName) {
                case 'h1':
                    $this->command->newLine();
                    $this->command->line('<fg=white;bg=blue;options=bold> ' . strtoupper($child->textContent) . ' </>');
                    $this->command->newLine();
                    break;

                case 'h2':
                    $this->command->newLine();
                    $this->command->line('<fg=cyan;options=bold>## ' . $child->textContent . '</>');
                    break;

                case 'h3':
                    $this->command->newLine();
                    $this->command->line('<fg=cyan>### ' . $child->textContent . '</>');
                    break;

                case 'p':
                    $text = $this->processInlineElements($child);
                    $this->command->line(str_repeat('  ', $indent) . $text);
                    $this->command->newLine();
                    break;

                case 'ul':
                case 'ol':
                    $this->processList($child, $indent);
                    break;

                case 'table':
                    $this->processTable($child);
                    break;

                case 'div':
                    $class = $child->getAttribute('class');
                    if (str_contains($class, 'alert')) {
                        $this->processAlert($child, $class);
                    } else {
                        $this->processNode($child, $indent);
                    }
                    break;

                case 'hr':
                    $this->command->line('<fg=gray>' . str_repeat('─', 60) . '</>');
                    break;

                case 'br':
                    $this->command->newLine();
                    break;

                default:
                    $this->processNode($child, $indent);
            }
        }
    }

    private function processInlineElements($node)
    {
        $text = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } else {
                switch ($child->nodeName) {
                    case 'strong':
                    case 'b':
                        $text .= '<options=bold>' . $child->textContent . '</>';
                        break;
                    case 'em':
                    case 'i':
                        $text .= '<fg=yellow>' . $child->textContent . '</>';
                        break;
                    case 'code':
                        $text .= '<fg=magenta;bg=black> ' . $child->textContent . ' </>';
                        break;
                    case 'a':
                        $href = $child->getAttribute('href');
                        $text .= '<fg=blue;options=underscore>' . $child->textContent . '</> <fg=gray>(' . $href . ')</>';
                        break;
                    default:
                        $text .= $child->textContent;
                }
            }
        }

        return $text;
    }

    private function processList($node, $indent = 0)
    {
        $counter = 1;
        $isOrdered = $node->nodeName === 'ol';

        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'li') {
                $bullet = $isOrdered ? "$counter." : '•';
                $text = $this->processInlineElements($child);
                $this->command->line(str_repeat('  ', $indent) . "<fg=green>$bullet</> $text");
                $counter++;
            }
        }
        $this->command->newLine();
    }

    private function processTable($node)
    {
        $headers = [];
        $rows = [];

        foreach ($node->childNodes as $section) {
            if ($section->nodeName === 'thead') {
                foreach ($section->childNodes as $tr) {
                    if ($tr->nodeName === 'tr') {
                        foreach ($tr->childNodes as $th) {
                            if ($th->nodeName === 'th') {
                                $headers[] = $th->textContent;
                            }
                        }
                    }
                }
            } elseif ($section->nodeName === 'tbody') {
                foreach ($section->childNodes as $tr) {
                    if ($tr->nodeName === 'tr') {
                        $row = [];
                        foreach ($tr->childNodes as $td) {
                            if ($td->nodeName === 'td') {
                                $row[] = $td->textContent;
                            }
                        }
                        if (!empty($row)) {
                            $rows[] = $row;
                        }
                    }
                }
            }
        }

        if (!empty($headers) && !empty($rows)) {
            $this->command->table($headers, $rows);
        }
    }

    private function processAlert($node, $class)
    {
        $text = $node->textContent;

        if (str_contains($class, 'success')) {
            $this->command->line('<fg=black;bg=green> ✓ ' . $text . ' </>');
        } elseif (str_contains($class, 'danger') || str_contains($class, 'error')) {
            $this->command->line('<fg=white;bg=red> ✗ ' . $text . ' </>');
        } elseif (str_contains($class, 'warning')) {
            $this->command->line('<fg=black;bg=yellow> ⚠ ' . $text . ' </>');
        } elseif (str_contains($class, 'info')) {
            $this->command->line('<fg=white;bg=blue> ℹ ' . $text . ' </>');
        } elseif (str_contains($class, 'primary')) {
            $this->command->line('<fg=white;bg=cyan> ' . $text . ' </>');
        } else {
            $this->command->line($text);
        }

        $this->command->newLine();
    }
}
