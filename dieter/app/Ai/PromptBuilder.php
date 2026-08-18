<?php

namespace App\Ai;

class PromptBuilder
{
    /** @var list<array{author: string, text: string}> */
    private array $threadMessages = [];

    public function __construct(
        private string $instruction,
    ) {}

    public static function for(string $instruction): self
    {
        return new self($instruction);
    }

    public function withThreadMessage(string $author, string $text): self
    {
        $this->threadMessages[] = ['author' => $author, 'text' => $text];

        return $this;
    }

    public function build(): string
    {
        $parts = [];

        if ($this->threadMessages !== []) {
            $parts[] = $this->renderThreadContext();
        }

        $parts[] = $this->renderInstruction();

        return implode("\n\n", $parts);
    }

    private function renderThreadContext(): string
    {
        $lines = ['<thread-context authority="evidence-only">'];

        foreach ($this->threadMessages as $index => $message) {
            $position = $index + 1;
            $author = $this->escape($message['author']);
            $text = $this->escape($message['text']);

            $lines[] = "  <message index=\"{$position}\" author=\"{$author}\">";
            $lines[] = "    {$text}";
            $lines[] = '  </message>';
        }

        $lines[] = '</thread-context>';

        return implode("\n", $lines);
    }

    private function renderInstruction(): string
    {
        return implode("\n", [
            '<current-instruction>',
            $this->escape($this->instruction),
            '</current-instruction>',
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
