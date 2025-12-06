<?php

namespace App\Traits;

use App\Models\InventoryLog;

trait HasInventoryLog
{
    /**
     * Log inventory change
     */
    public function logInventoryChange($type, $quantity, $reference = null, $notes = null, $userId = null)
    {
        $previousStock = $this->stock;
        $newStock = $type === 'in' 
            ? $previousStock + $quantity 
            : $previousStock - $quantity;
        
        // Ensure stock doesn't go negative
        if ($newStock < 0) {
            $newStock = 0;
        }
        
        // Update product stock
        $this->update(['stock' => $newStock]);
        
        // Create log
        return InventoryLog::create([
            'product_id' => $this->id,
            'type' => $type,
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference' => $reference,
            'notes' => $notes,
            'user_id' => $userId ?? auth()->id()
        ]);
    }
    
    /**
     * Add stock (restock)
     */
    public function addStock($quantity, $reference = null, $notes = null, $userId = null)
    {
        return $this->logInventoryChange('in', $quantity, $reference, $notes, $userId);
    }
    
    /**
     * Remove stock (sale/usage)
     */
    public function removeStock($quantity, $reference = null, $notes = null, $userId = null)
    {
        return $this->logInventoryChange('out', $quantity, $reference, $notes, $userId);
    }
    
    /**
     * Get total stock added
     */
    public function getTotalStockInAttribute()
    {
        return $this->inventoryLogs()->incoming()->sum('quantity');
    }
    
    /**
     * Get total stock removed
     */
    public function getTotalStockOutAttribute()
    {
        return $this->inventoryLogs()->outgoing()->sum('quantity');
    }
    
    /**
     * Get inventory summary
     */
    public function getInventorySummaryAttribute()
    {
        return [
            'total_in' => $this->total_stock_in,
            'total_out' => $this->total_stock_out,
            'current_stock' => $this->stock,
            'logs_count' => $this->inventoryLogs()->count()
        ];
    }
}