<?php
namespace samdark\sitemap;

/**
 * WriterInterface represents a data sink
 *
 * Data is successively given by calling append. After calling finish all of it
 * should have been written to the target.
 */
interface WriterInterface
{
    /**
     * Queue data for writing to the target
     *
     * @param string $data
     */
    public function append($data);

    /**
     * Ensure all queued data is written and close the target
     *
     * No further data may be appended after this.
     */
    public function finish();
}
