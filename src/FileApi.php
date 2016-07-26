<?php

namespace Recca0120\Upload;

class FileApi extends Api
{
    /**
     * getOriginalName.
     *
     * @method getOriginalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        $name = $this->request->get('name');
        if (is_null($name) === false) {
            return $name;
        }

        list($name) = sscanf($this->request->header('content-disposition'), 'attachment; filename=%s');

        return $name;
    }

    /**
     * hasChunks.
     *
     * @method hasChunks
     *
     * @return bool
     */
    public function hasChunks()
    {
        return is_null($this->request->header('content-range')) === false;
    }

    /**
     * getStartOffset.
     *
     * @method getStartOffset
     *
     * @return int
     */
    public function getStartOffset()
    {
        $contentRange = $this->request->header('content-range');
        list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');

        return $start;
    }

    /**
     * isCompleted.
     *
     * @method isCompleted
     *
     * @return bool
     */
    public function isCompleted()
    {
        $contentRange = $this->request->header('content-range');
        list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');

        return $end >= $total - 1;
    }

    /**
     * getMimeType.
     *
     * @method getMimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->request->header('content-type');
    }

    /**
     * getResourceName.
     *
     * @method getResourceName
     *
     * @return string
     */
    public function getResourceName()
    {
        return 'php://input';
    }
}
