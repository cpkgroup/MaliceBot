<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/25/16
 * Time: 3:21 PM
 */

namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Commands\Constants\CommandList;
use Longman\TelegramBot\Commands\Command;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Commands\Sections\SectionInterface;
use Trumpet\TelegramBot\Engine\Injectable;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class StatusService extends Injectable
{
    /**
     * @param $userId
     * @param $currentCommand
     * @param array $commandHistory
     * @param bool $resetCommandHistory
     */
    public function setStatus($userId, $currentCommand, $commandHistory = [], $resetCommandHistory = false)
    {
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $userCurrentCommand = null;
        if ($resetCommandHistory) {
            $commandHistory = [];
            $currentCommand = null;
        } else if (!$commandHistory) {
            $commandHistory = isset($userInfo['commandHistory']) ? $userInfo['commandHistory'] : [];
            $commandHistoryCount = count($commandHistory);
            $userCurrentCommand = isset($commandHistory[$commandHistoryCount - 1]) ? $commandHistory[$commandHistoryCount - 1] : null;
        }
        if ($currentCommand && $currentCommand != $userCurrentCommand) {
            $commandHistory[] = $currentCommand;
        }
        $userInfo['commandHistory'] = $commandHistory;
        $this->messageHelper->setUserInfo($userId, $userInfo);
    }

    /**
     * @param $userId
     * @param $userInfo
     * @param $status
     */
    public function removeStatus($userId, $userInfo, $status)
    {
        $userInfo = $userInfo ? $userInfo : $this->messageHelper->getUserInfo($userId);
        // update command history
        $userInfo['commandHistory'] = array_filter($userInfo['commandHistory'], function ($v) use ($status) {
            if ($v == $status) {
                return false;
            }
            return true;
        });
        $this->messageHelper->setUserInfo($userId, $userInfo);
    }

    /**
     * @param $userId
     * @return string|null
     */
    public function getStatus($userId)
    {
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $commandHistory = isset($userInfo['commandHistory']) ? $userInfo['commandHistory'] : [];
        $commandHistoryCount = count($commandHistory);
        $currentCommand = isset($commandHistory[$commandHistoryCount - 1]) ? $commandHistory[$commandHistoryCount - 1] : null;
        return $currentCommand;
    }

    public function handleStatus(Command $command)
    {
        $message = MessageDirector::build($command);
        $this->beforeAction($message);
        $userId = $message->getUserId();
        $text = $message->getText();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $commandHistory = isset($userInfo['commandHistory']) ? $userInfo['commandHistory'] : [];
        $lastCommand = null;
        $currentCommand = null;

        if ($commandHistory) {
            $commandHistoryCount = count($commandHistory);
            $lastCommand = isset($commandHistory[$commandHistoryCount - 2]) ? $commandHistory[$commandHistoryCount - 2] : null;
            $currentCommand = isset($commandHistory[$commandHistoryCount - 1]) ? $commandHistory[$commandHistoryCount - 1] : null;
        }

        switch ($text) {
            case CommandList::BACK:
            case _C('BACK'):
                $message->setText(null);
                if ($currentCommand) {
                    unset($commandHistory[count($commandHistory) - 1]);
                }
                $currentCommand = $lastCommand;
                $this->setStatus($userId, null, $commandHistory, $lastCommand ? false : true);
                break;
            case CommandList::MAIN_MENU:
            case _C('MAIN_MENU'):
                $message->setText(null);
                $currentCommand = StatusList::START;
                break;
        }

        if (!$currentCommand) {
            switch ($text) {
                case CommandList::POST_LISTING:
                case _C('POST_LISTING'):
                    $currentCommand = StatusList::POST_LISTING;
                    break;
                case CommandList::SHOW_LISTING:
                case _C('SHOW_LISTING'):
                    $currentCommand = StatusList::SHOW_LISTING_START;
                    break;
                case CommandList::MY_LISTING:
                case _C('MY_LISTING'):
                    $currentCommand = StatusList::MY_LISTING;
                    break;
                case CommandList::SUPPORT:
                case _C('SUPPORT'):
                    $currentCommand = StatusList::SUPPORT;
                    break;
                case CommandList::DOWNLOAD_APP:
                case _C('DOWNLOAD_APP'):
                    $currentCommand = StatusList::DOWNLOAD;
                    break;
                default:
                    $currentCommand = StatusList::START;
            }
        }

        return $this->messageHelper->executeSection($currentCommand, $message);
    }

    private function beforeAction(InputMessageAbstract $message)
    {
        if ($message->getLocation()) {
            $userInfo = $this->location->setLocation($message);
            if (isset($userInfo['location']['data']['id'])) {
                $this->messageHelper->sendMessage($message->getUserId(), _T('Location submitted successfully', [
                    ':region' => $userInfo['location']['data']['name']
                ]));
            }
        }

        if ($message->getContact()) {
            $userInfo = $this->messageHelper->setContact($message);
            if (!$userInfo) {
                $this->messageHelper->sendMessage($message->getUserId(), _T('Profile entered is not yours'));
            } else if (isset($userInfo['contact']['mobile'])) {
                $this->messageHelper->sendMessage($message->getUserId(), _T('Contact information submitted successfully', [
                    ':mobile' => $userInfo['contact']['mobile']
                ]));
            }
        }
    }
}
