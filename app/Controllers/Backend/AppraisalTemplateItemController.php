<?php

namespace App\Controllers;

use App\Models\TemplateItemModel;
use App\Services\TemplateService;
use Exception;

class AppraisalTemplateItemController extends BaseController
{
    protected $format = 'json';
    protected TemplateService $templateService;

    public function __construct()
    {
        $this->templateService = new TemplateService();
    }

    /**
     * POST /templates/{templateId}/items
     * Add an item to a draft template.
     */
    public function createItem($templateId = null)
    {
        $rules = [
            'category'       => 'required|in_list[' . TemplateItemModel::CAT_PERFORMA . ',' . TemplateItemModel::CAT_KOMPETENSI_TAMBAHAN . ']',
            'competency'     => 'permit_empty|string|max_length[255]',
            'definition'     => 'permit_empty|string',
            'key_actions'    => 'permit_empty|string',
            'input_type'     => 'required|in_list[' . TemplateItemModel::INPUT_SCORE . ',' . TemplateItemModel::INPUT_CURRENCY . ',' . TemplateItemModel::INPUT_TEXT . ',' . TemplateItemModel::INPUT_BOOLEAN . ',' . TemplateItemModel::INPUT_COMPETENCY_PICK . ']',
            'min_value'      => 'permit_empty|numeric',
            'max_value'      => 'permit_empty|numeric',
            'weight'         => 'required|numeric',
            'sequence_order' => 'required|integer',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $userId = (int) ($this->request->getHeaderLine('X-User-ID') ?: 0);
            $itemId = $this->templateService->addItem((int) $templateId, $this->request->getPost(), $userId);

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Template item added.',
                'data'    => ['template_item_id' => $itemId],
            ]);
        } catch (Exception $e) {
            return $this->failBadRequest($e->getMessage());
        }
    }

    /**
     * PUT /template-items/{id}
     * Update an item in a draft template.
     */
    public function updateItem($id = null)
    {
        try {
            $userId = (int) ($this->request->getHeaderLine('X-User-ID') ?: 0);
            $this->templateService->updateItem((int) $id, $this->request->getRawInput(), $userId);

            return $this->respond([
                'status'  => 200,
                'message' => 'Template item updated.',
            ]);
        } catch (Exception $e) {
            return $this->failBadRequest($e->getMessage());
        }
    }

    /**
     * DELETE /template-items/{id}
     * Remove an item from a draft template.
     */
    public function deleteItem($id = null)
    {
        try {
            $userId = (int) ($this->request->getHeaderLine('X-User-ID') ?: 0);
            $this->templateService->deleteItem((int) $id, $userId);

            return $this->respond([
                'status'  => 200,
                'message' => 'Template item deleted.',
            ]);
        } catch (Exception $e) {
            return $this->failBadRequest($e->getMessage());
        }
    }
}