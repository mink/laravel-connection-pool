<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Concerns;

trait HasLabels
{
    /**
     * The labels associated with the connection.
     *
     * @var string[]
     */
    private array $labels = [];

    /**
     * Obtains the connection labels.
     *
     * @return string[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Adds a label to the connection.
     *
     * @param string $label
     * @return $this
     */
    public function addLabel(string $label): self
    {
        $this->labels[] =  $label;

        return $this;
    }

    /**
     * Removes a label from the connection.
     *
     * @param string $labelToRemove
     * @return $this
     */
    public function removeLabel(string $labelToRemove): self
    {
        $this->labels = array_filter($this->labels, function($label) use($labelToRemove) {
            return $label !== $labelToRemove;
        });

        return $this;
    }
}
