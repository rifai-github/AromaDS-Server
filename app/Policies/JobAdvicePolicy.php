<?php

namespace App\Policies;

use App\Models\User;
use App\Models\JobAdvice;

class JobAdvicePolicy
{
    /**
     * MOM6: Access control per JA type
     * "jangan lupa per JA ini ada hak akses per typenya, maksudnya adalah ga semua user bisa akses"
     */

    /**
     * Determine if the user can view any job advices
     */
    public function viewAny(User $user)
    {
        // Only users with specific roles can view job advices
        return $user->hasAnyRole(['admin', 'marketing', 'operations', 'manager']);
    }

    /**
     * Determine if the user can view the job advice
     */
    public function view(User $user, JobAdvice $jobAdvice)
    {
        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Marketing can view install/install_new types
        if ($user->hasRole('marketing') || $user->hasRole('sales')) {
            return in_array($jobAdvice->type, ['install', 'installation', 'install_new']);
        }

        // Operations can view service/remove/maintenance types
        if ($user->hasRole('operations') || $user->hasRole('technician')) {
            return in_array($jobAdvice->type, ['service', 'remove', 'removal', 'maintenance']);
        }

        // Manager can view all
        if ($user->hasRole('manager')) {
            return true;
        }

        // Check if user is the submitter or approver
        return $jobAdvice->submitted_by === $user->id || 
               $jobAdvice->approved_by === $user->id;
    }

    /**
     * Determine if the user can create job advices
     */
    public function create(User $user)
    {
        // Marketing and Operations can create
        return $user->hasAnyRole(['admin', 'marketing', 'operations', 'manager']);
    }

    /**
     * Determine if the user can update the job advice
     */
    public function update(User $user, JobAdvice $jobAdvice)
    {
        // Can't update if already approved
        if ($jobAdvice->status === 'approved') {
            return false;
        }

        // Admin can update all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Only creator can update draft/rejected
        if (in_array($jobAdvice->status, ['draft', 'rejected'])) {
            return $jobAdvice->submitted_by === $user->id || $jobAdvice->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the job advice
     */
    public function delete(User $user, JobAdvice $jobAdvice)
    {
        // Can't delete if already approved
        if ($jobAdvice->status === 'approved') {
            return false;
        }

        // Admin can delete all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Only creator can delete draft
        if ($jobAdvice->status === 'draft') {
            return $jobAdvice->submitted_by === $user->id || $jobAdvice->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can approve the job advice
     */
    public function approve(User $user, JobAdvice $jobAdvice)
    {
        // Only manager/admin can approve
        if (!$user->hasAnyRole(['admin', 'manager'])) {
            return false;
        }

        // Must be in submitted status
        return $jobAdvice->status === 'submitted';
    }

    /**
     * Determine if the user can reject the job advice
     */
    public function reject(User $user, JobAdvice $jobAdvice)
    {
        // Only manager/admin can reject
        if (!$user->hasAnyRole(['admin', 'manager'])) {
            return false;
        }

        // Must be in submitted status
        return $jobAdvice->status === 'submitted';
    }
}

