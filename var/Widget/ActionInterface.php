<?php

namespace Widget;

/**
 * Giao diện có thể được gọi bởi Widget\Action
 */
interface ActionInterface
{
    /**
     * Chức năng đầu vào cần được thực hiện bởi giao diện
     */
    public function action();
}
