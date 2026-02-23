<?php

namespace App\Presenters;

use App\Helpers\IconHelper;

/**
 * Class CompanyPresenter
 */
class ActionlogPresenter extends Presenter
{
    private function isConsumableReset(): bool
    {
        return ($this->action_type === 'update')
            && is_string($this->note)
            && str_starts_with($this->note, 'Consumable replenished');
    }

    public function adminuser()
    {
        if ($user = $this->model->user) {
            if (empty($user->deleted_at)) {
                return $user->present()->nameUrl();
            }
            // The user was deleted
            return '<del>'.$user->display_name.'</del> (deleted)';
        }

        return '';
    }

    public function item()
    {
        if ($this->action_type == 'uploaded') {
            return (string) link_to_route('show/userfile', $this->model->filename, [$this->model->item->id, $this->model->id]);
        }
        if ($item = $this->model->item) {
            if (empty($item->deleted_at)) {
                return $this->model->item->present()->nameUrl();
            }
            // The item was deleted
            return '<del>'.$item->name.'</del> (deleted)';
        }

        return '';
    }

    public function icon()
    {

        if ($this->isConsumableReset()) {
            return IconHelper::icon('replenish');
        }

        // User related icons
        if ($this->itemType() == 'user') {

            if ($this->action_type == '2fa reset') {
                return IconHelper::icon('2fa reset');
            }

            if ($this->action_type == 'create') {
                return IconHelper::icon('new-user');
            }

            if ($this->action_type == 'merged') {
                return IconHelper::icon('merged-user');
            }

            if ($this->action_type == 'delete') {
                return IconHelper::icon('delete-user');
            }

            if ($this->action_type == 'delete') {
                return IconHelper::icon('delete-user');
            }

            if ($this->action_type == 'upload deleted') {
                return IconHelper::icon('upload deleted');
            }

            if ($this->action_type == 'update') {
                return IconHelper::icon('update-user');
            }

             return IconHelper::icon('user');
        }

        // Everything else
        if ($this->action_type == 'create') {
            return IconHelper::icon('create');
        }

        if (($this->action_type == 'delete') || ($this->action_type == 'upload deleted')) {
            return IconHelper::icon('delete');
        }

        if ($this->action_type == 'update') {
            return IconHelper::icon('edit');
        }

        if ($this->action_type == 'restore') {
            return IconHelper::icon('restore');
        }

        if ($this->action_type == 'upload') {
            return IconHelper::icon('paperclip');
        }

        if ($this->action_type == 'checkout') {
            return IconHelper::icon('checkout');
        }

        if ($this->action_type == 'checkin from') {
            return IconHelper::icon('checkin');
        }

        if ($this->action_type == 'note added') {
            return IconHelper::icon('note');
        }

        if ($this->action_type == 'audit') {
            return IconHelper::icon('audit');
        }

        return IconHelper::icon('checkin');

    }

    public function actionType()
    {
        if ($this->isConsumableReset()) {
            return 'replenish';
        }

        return mb_strtolower(trans('general.'.str_replace(' ', '_', $this->action_type)));
    }

    public function target()
    {
        $target = null;
        // Target is messy.
        // On an upload, the target is the item we are uploading to, stored as the "item" in the log.
        if ($this->action_type == 'uploaded') {
            $target = $this->model->item;
        } elseif (($this->action_type == 'accepted') || ($this->action_type == 'declined')) {
            // If we are logging an accept/reject, the target is not stored directly,
            // so we access it through who the item is assigned to.
            // FIXME: On a reject it's not assigned to anyone.
            $target = $this->model->item->assignedTo;
        } elseif ($this->action_type == 'requested') {
            if ($this->model->user) {
                $target = $this->model->user;
            }
        } elseif ($this->model->target) {
            // Otherwise, we'll just take the target of the log.
            $target = $this->model->target;
        }

        if ($target) {
            if (empty($target->deleted_at)) {
                return $target->present()->nameUrl();
            }

            return '<del>'.$target->display_name.'</del>';
        }

        return '';
    }
}
