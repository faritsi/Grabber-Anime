<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataResource extends JsonResource
{
    public $status;
    public $pesan;
    public $resource;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function __construct($status, $pesan, $resource) {
        parent::__construct($resource);
        $this->status = $status;
        $this->pesan = $pesan;
    }
    public function toArray($request)
    {
        return [
            'sukses' => $this->status,
            'pesan' => $this->pesan,
            'data' => $this->resource,
        ];
    }
    // public function toArray(Request $request): array
    // {
    //     return parent::toArray($request);
    // }
}
