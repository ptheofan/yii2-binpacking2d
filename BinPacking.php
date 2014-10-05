<?php
/**
 * Created by PhpStorm.
 * User: ptheofan
 * Date: 02/10/14
 * Time: 17:59
 */

namespace ptheofan\binpacking2d;

use yii\base\Object;

/**
 * Class BinPacking
 * @package ptheofan\binpacking2d
 *
 * The implementation is based on the js bin packing implementation at
 * https://github.com/jakesgordon/bin-packing
 */
class BinPacking extends Object
{
    public $w = null;
    public $h = null;
    public $sizeAuto = true;

    /**
     * @var Rect $root
     */
    protected $root;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->root = new Node(['w' => $this->w, 'h' => $this->h]);
    }

    /**
     * @param Node[] $sprites
     * @throws Exception
     */
    public function fit(array $sprites)
    {
        // Sort by maxside
        uasort($sprites, function($a, $b) { return max($b->w, $b->h) - max($a->w, $a->h); });
        uasort($sprites, function($a, $b) { return min($b->w, $b->h) - min($a->w, $a->h); });
        uasort($sprites, function($a, $b) { return $b->h - $a->h; });
        uasort($sprites, function($a, $b) { return $b->w - $a->w; });

        if ($this->sizeAuto) {
            $sprite = reset($sprites);
            $this->w = $sprite->w;
            $this->h = $sprite->h;

            $this->root->w = $this->w;
            $this->root->h = $this->h;
        }

        foreach($sprites as $idx => $sprite)
        {
            if (!$sprite instanceof INode)
                throw new Exception("All nodes must be instances of INode");

            $node = $this->findNode($this->root, $sprite->w, $sprite->h);
            if ($node) {
                $sprite->fit = $this->splitNode($node, $sprite->w, $sprite->h);
                if (!$sprite->fit)
                    throw new Exception("Not enough space for all the sprites!");
                $sprite->x = $sprite->fit->x;
                $sprite->y = $sprite->fit->y;
            } elseif ($this->sizeAuto) {
                $sprite->fit = $this->growNode($sprite->w, $sprite->h);
                if (!$sprite->fit)
                    throw new Exception("Not enough space for all the sprites!");
                $sprite->x = $sprite->fit->x;
                $sprite->y = $sprite->fit->y;

                if ($this->w < $sprite->x + $sprite->w)
                    $this->w = $sprite->x + $sprite->w;
                if ($this->h < $sprite->y + $sprite->h)
                    $this->h = $sprite->y + $sprite->h;
            } else {
                throw new Exception("Not enough space for all the sprites!");
            }
        }

        return $sprites;
    }

    /**
     * @param Node $root
     * @param $w
     * @param $h
     * @return Node|null
     */
    public function findNode(Node $root, $w, $h)
    {
        if ($root->used) {
            $node = $this->findNode($root->right, $w, $h);
            if (!$node)
                $node = $this->findNode($root->down, $w, $h);
            return $node;
        } elseif (($w <= $root->w) && ($h <= $root->h))
            return $root;
        else
            return null;
    }

    /**
     * @param Node $node
     * @param $w
     * @param $h
     * @return Node
     */
    public function splitNode(Node $node, $w, $h)
    {
        $node->used = true;
        $node->down = new Node([
            'x' => $node->x,
            'y' => $node->y + $h,
            'w' => $node->w,
            'h' => $node->h - $h,
        ]);
        $node->right = new Node([
            'x' => $node->x + $w,
            'y' => $node->y,
            'w' => $node->w - $w,
            'h' => $node->h,
        ]);

        return $node;
    }

    /**
     * @param $w
     * @param $h
     * @return Node|null
     */
    public function growNode($w, $h)
    {
        $canGrowDown = $w <= $this->root->w;
        $canGrowRight = $h <= $this->root->h;

        $shouldGrowRight = $canGrowRight && (($this->root->h >= $this->root->w + $w));
        $shouldGrowDown = $canGrowDown && (($this->root->w >= $this->root->h + $h));

        if ($shouldGrowRight)
            return $this->growRight($w, $h);
        elseif ($shouldGrowDown)
            return $this->growDown($w, $h);
        elseif ($canGrowRight)
            return $this->growRight($w, $h);
        elseif ($canGrowDown)
            return $this->growDown($w, $h);
        else
            return null;
    }

    /**
     * @param $w
     * @param $h
     * @return Node|null
     */
    public function growRight($w, $h)
    {
        $node = new Node([
            'used' => true,
            'x' => 0,
            'y' => 0,
            'w' => $this->root->w + $w,
            'h' => $this->root->h,
            'down' => $this->root,
            'right' => new Node([
                'x' => $this->root->w,
                'y' => 0,
                'w' => $w,
                'h' => $this->root->h,
            ]),
        ]);
        $this->root = $node;

        $node = $this->findNode($this->root, $w, $h);
        if ($node)
            return $this->splitNode($node, $w, $h);
        else
            return null;
    }

    /**
     * @param $w
     * @param $h
     * @return Node|null
     */
    public function growDown($w, $h)
    {
        $node = new Node([
            'used' => true,
            'x' => 0,
            'y' => 0,
            'w' => $this->root->w,
            'h' => $this->root->h + $h,
            'down' => new Node([
                'x' => 0,
                'y' => $this->root->h,
                'w' => $this->root->w,
                'h' => $h,
            ]),
            'right' => $this->root,
        ]);
        $this->root = $node;

        $node = $this->findNode($this->root, $w, $h);
        if ($node)
            return $this->splitNode($node, $w, $h);
        else
            return null;
    }
} 