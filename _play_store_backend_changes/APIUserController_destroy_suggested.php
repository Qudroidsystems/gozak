<?php
/*
 * SUGGESTED replacement for APIUserController::destroy() — NOT applied.
 *
 * Why: users.id is cascade-deleted by credit_accounts, orders, payments, bank accounts, chat, reviews.
 * Today a customer with an unpaid Gozak Credit balance can delete their account and the debt record
 * disappears with it. This blocks deletion while money is owed, which also makes the wording on
 * /delete-account ("once any outstanding balance is settled") true.
 *
 * Also: do not return $e->getMessage() to clients (information leak).
 * Needs:  use App\Models\Credit\CreditAccount;
 */
public function destroy(Request $request)
{
    try {
        $user = $request->user();

        $owed = CreditAccount::where('user_id', $user->id)->value('balance');
        $unbilled = CreditAccount::where('user_id', $user->id)->value('unbilled');
        if (((float) $owed + (float) $unbilled) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'You have an outstanding Gozak Credit balance. Please settle it before closing your account.',
            ], 422);
        }

        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // Revoke API tokens and push token so nothing keeps working after deletion
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'Account deleted successfully'], 200);
    } catch (\Exception $e) {
        Log::error('Delete account error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to delete account'], 500);
    }
}
