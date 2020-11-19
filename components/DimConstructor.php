<?php

namespace d3acc\components;

use Exception;
use d3acc\models\AcDimGroup;
use d3acc\models\AcDim;

class  DimConstructor
{
    /**
     * @var array AcDimGroup
     */
    private $ac_dim_groups = [];
    private $sysCompanyId;

    /**
     * DimConstructor constructor.
     * @param $sysCompanyId
     */
    public function __construct($sysCompanyId)
    {
        $this->sysCompanyId = $sysCompanyId;
    }


    /**
     * @param string $name
     * Adds new virtual dimension group
     * Group names must be unique
     * @param int $id
     * @throws Exception
     */
    public function addGroup(string $name, int $id = 0): void
    {
        $group = new AcDimGroup();
        if($id){
            $group->id = $id;
        }
        $group->sys_company_id = $this->sysCompanyId;
        $group->name = $name;
        if (!$group->save()) {
            throw new Exception('Can not create AcDimGroup: ' . json_encode($group->getErrors()));
        }
    }

    /**
     * @param string $groupName
     * @param string $dimensionName
     * @param int $id
     * @throws Exception Adds new virtual dimension
     */
    public function addGroupDimension(
        string $groupName,
        string $dimensionName,
        int $id = 0
    ): void
    {
        $accDim = new AcDim();
        if($id){
            $accDim->id = $id;
        }
        $accDim->sys_company_id = $this->sysCompanyId;
        $accDim->name = $dimensionName;
        $accDim->group_id = $this->getAccDimGroupId($groupName);
        if (!$accDim->save()) {
            throw new Exception('Can not create AcDimGroup: ' . json_encode($accDim->getErrors()));
        }
    }

    /**
     * @param string $groupName
     * @return int
     * @throws Exception
     * Gets virtual diemansion group by name, this sets requirement for unique group names
     */
    private function getAccDimGroupId(string $groupName): int
    {
        if (empty($this->ac_dim_groups)) {
            /** @var AcDimGroup $acDimGroup */
            foreach (AcDimGroup::find()->where(['sys_company_id' => $this->sysCompanyId])->all() as $acDimGroup) {
                $this->ac_dim_groups[$acDimGroup->name] = $acDimGroup;
            }
            if (empty($this->ac_dim_groups)) {
                throw new Exception('No dimmension groups found!');
            }
        }
        /** @var AcDimGroup $acDimGroup */
        $acDimGroup = $this->ac_dim_groups[$groupName];
        return $acDimGroup->id;
    }
}