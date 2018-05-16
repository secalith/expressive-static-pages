<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Stock\Model;

use Common\Model\WriteTableInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

class StockTable implements WriteTableInterface
{
    /**
     * @var TableGateway
     */
    protected $tableGateway;

    /**
     * ProductTable constructor.
     * @param TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    public function fetchAllFull()
    {
        $sqlSelect = $this->tableGateway->getSql()->select();

        $sqlSelect->columns(array('stock_uid','product_qty'));
        $sqlSelect->join('product', 'product.product_uid = stock.product_uid', array('name','price','description_short','unit'), 'left');

        $statement = $this->tableGateway->getSql()->prepareStatementForSqlObject($sqlSelect);
        $resultSet = $statement->execute();

        return $resultSet;
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAll()
    {
        $resultSet = $this->tableGateway->select();
        $resultSet->buffer();
        $resultSet->next();

        return $resultSet;
    }

    /**
     * @param integer $id
     * @return array|\ArrayObject|null
     * @throws \Exception
     */
    public function getItem($id)
    {
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->where(['stock.product_uid' => $id]);
        $sqlSelect->columns(['stock_uid','product_uid','product_qty','created','updated']);
        $sqlSelect->join(
            'product',
            'product.product_uid = stock.product_uid',
            [
                'name',
                'price',
                'description_short',
                'unit'
            ],
            'left'
        );
        $sqlSelect->limit(1);

        $statement = $this->tableGateway->getSql()->prepareStatementForSqlObject($sqlSelect);
        $resultSet = $statement->execute();


        $row = $resultSet->current();
        if (!$row) {
            return null;
        }

        $row = new StockProductModel($row);
        
        return $row;
    }

    public function getItemCount($product_uid = null) : int
    {
        if ($product_uid===null) {
            return 0;
        }
        if (is_array($product_uid)) {
            $rowset = $this->tableGateway->select($product_uid);
        } else {
            $rowset = $this->tableGateway->select(['product_uid' => $product_uid]);
        }

        return $rowset->count();
    }

    /**
     * @param string $value
     * @param string $name
     * @throws \Exception
     */
    public function fetchBy($value, $name = "product_id")
    {
        $rowset = $this->tableGateway->select([$name => $value]);
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $value");
        }
        return $row;
    }

    /**
     * @param \Cart\Model\CartModel $item
     */
    public function saveItem($item)
    {
        $updated = new \DateTime('now');

        $data = [
            'product_id' => $item->getProductId(),
            'code' => strtoupper($item->getCode()),
        ];
        $this->tableGateway->insert($data);
    }

    public function deleteItem($item)
    {
        $data = ['product_id' => $item->getProductId(),'code' => $item->getProductId()];
        $this->tableGateway->delete($data);
    }

    public function updateItem($uid,$data)
    {
        $rowsAffected = $this->tableGateway->update($data, ['product_uid' => $uid]);

        return $rowsAffected;
    }

    /**
     * @param \Cart\Model\CartModel $item
     */
    public function updateStatus($item)
    {

        $data = [
            'status' => $item->getStatus(),
        ];

        $cartId = $item->getCartId();
//        $cartProductId = $item->getProductId();

        if (null===$cartId || 0===$cartId) {
            throw new \Exception('Item\'s `cartId` cannot be null');
        } else {
            if ($this->getItem($cartId)) {
                $this->tableGateway->update($data, ['cart_id' => $cartId]);
            } else {
                throw new \Exception('Item\'s `cartId` does not exist');
            }
        }
    }

}
