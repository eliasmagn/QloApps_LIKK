<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/license/osl-3-0-php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@qloapps.com so we can send you a copy immediately.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class KlOperationTaskGenerator
{
    /**
     * @var Module
     */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Execute the daily generation workflow for the provided date.
     *
     * @param DateTimeImmutable $targetDate
     *
     * @return int Number of tasks generated during this run
     */
    public function runDaily(DateTimeImmutable $targetDate)
    {
        $run = new KlOperationRun();
        $run->run_type = 'daily_housekeeping';
        $run->status = 'running';
        $run->timezone = $targetDate->getTimezone()->getName();
        $run->started_at = $targetDate->format('Y-m-d 00:00:00');
        $run->date_add = date('Y-m-d H:i:s');
        $run->date_upd = $run->date_add;
        $run->metadata = $this->encodeMetadata(array(
            'target_date' => $targetDate->format('Y-m-d'),
        ));
        $run->add();

        $total = 0;
        $total += $this->generateCheckoutTasks($targetDate, $run);
        $total += $this->generateArrivalTasks($targetDate, $run);

        $run->status = 'completed';
        $run->completed_at = date('Y-m-d H:i:s');
        $run->metadata = $this->encodeMetadata(array(
            'target_date' => $targetDate->format('Y-m-d'),
            'tasks_created' => $total,
        ));
        $run->date_upd = date('Y-m-d H:i:s');
        $run->update();

        return $total;
    }

    /**
     * Synchronise housekeeping tasks for a booking record when the booking changes.
     *
     * @param HotelBookingDetail $booking
     */
    public function syncBookingTasks(HotelBookingDetail $booking)
    {
        if ((int) $booking->id_status === (int) HotelBookingDetail::STATUS_CHECKED_OUT) {
            $this->markTasksCompleted($booking->id, 'housekeeping_checkout');
        }

        if ((int) $booking->id_status === (int) HotelBookingDetail::STATUS_CHECKED_IN) {
            $this->markTasksInProgress($booking->id, 'housekeeping_arrival');
        }
    }

    private function generateCheckoutTasks(DateTimeImmutable $targetDate, KlOperationRun $run)
    {
        $checkoutTime = $this->createDateTime($targetDate, 10, 0);
        $dueTime = $this->createDateTime($targetDate, 16, 0);

        $bookings = $this->getBookingsForDate($targetDate, 'date_to');
        $count = 0;
        foreach ($bookings as $booking) {
            $uniqueKey = sprintf('booking:%d:checkout:%s', $booking['id'], $targetDate->format('Ymd'));
            if (KlOperationTask::getIdByUniqueKey($uniqueKey)) {
                continue;
            }

            $task = new KlOperationTask();
            $task->id_kl_operation_run = (int) $run->id;
            $task->reference = sprintf('HK-%s-%d', $targetDate->format('ymd'), $booking['id']);
            $task->task_type = 'housekeeping_checkout';
            $task->status = 'pending';
            $task->resource_type = 'room';
            $task->id_resource = (int) $booking['id_room'];
            $task->context_type = 'booking';
            $task->context_id = (int) $booking['id'];
            $task->scheduled_for = $checkoutTime->format('Y-m-d H:i:s');
            $task->due_end = $dueTime->format('Y-m-d H:i:s');
            $task->timezone = $targetDate->getTimezone()->getName();
            $task->payload = $this->encodeMetadata(array(
                'hotel_name' => $booking['hotel_name'],
                'room_name' => $booking['room_type_name'],
                'guest_name' => $booking['guest_name'],
                'stay' => array(
                    'from' => $booking['date_from'],
                    'to' => $booking['date_to'],
                ),
            ));
            $task->unique_key = $uniqueKey;
            $task->priority = 3;
            $task->date_add = date('Y-m-d H:i:s');
            $task->date_upd = $task->date_add;
            $task->add();
            $count++;
        }

        return $count;
    }

    private function generateArrivalTasks(DateTimeImmutable $targetDate, KlOperationRun $run)
    {
        $prepTime = $this->createDateTime($targetDate, 14, 0);
        $dueTime = $this->createDateTime($targetDate, 18, 0);

        $bookings = $this->getBookingsForDate($targetDate, 'date_from');
        $count = 0;
        foreach ($bookings as $booking) {
            $uniqueKey = sprintf('booking:%d:arrival:%s', $booking['id'], $targetDate->format('Ymd'));
            if (KlOperationTask::getIdByUniqueKey($uniqueKey)) {
                continue;
            }

            $task = new KlOperationTask();
            $task->id_kl_operation_run = (int) $run->id;
            $task->reference = sprintf('AR-%s-%d', $targetDate->format('ymd'), $booking['id']);
            $task->task_type = 'housekeeping_arrival';
            $task->status = 'pending';
            $task->resource_type = 'room';
            $task->id_resource = (int) $booking['id_room'];
            $task->context_type = 'booking';
            $task->context_id = (int) $booking['id'];
            $task->scheduled_for = $prepTime->format('Y-m-d H:i:s');
            $task->due_end = $dueTime->format('Y-m-d H:i:s');
            $task->timezone = $targetDate->getTimezone()->getName();
            $task->payload = $this->encodeMetadata(array(
                'hotel_name' => $booking['hotel_name'],
                'room_name' => $booking['room_type_name'],
                'guest_name' => $booking['guest_name'],
                'stay' => array(
                    'from' => $booking['date_from'],
                    'to' => $booking['date_to'],
                ),
            ));
            $task->unique_key = $uniqueKey;
            $task->priority = 2;
            $task->date_add = date('Y-m-d H:i:s');
            $task->date_upd = $task->date_add;
            $task->add();
            $count++;
        }

        return $count;
    }

    private function markTasksCompleted($contextId, $taskType)
    {
        $this->updateTaskStatus($contextId, $taskType, 'completed');
    }

    private function markTasksInProgress($contextId, $taskType)
    {
        $this->updateTaskStatus($contextId, $taskType, 'in_progress');
    }

    private function updateTaskStatus($contextId, $taskType, $status)
    {
        $data = array(
            'status' => pSQL($status),
            'date_upd' => date('Y-m-d H:i:s'),
        );

        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        Db::getInstance()->update(
            KlOperationTask::$definition['table'],
            $data,
            '`context_type` = "booking" AND `context_id` = ' . (int) $contextId . ' AND `task_type` = "' . pSQL($taskType) . '"'
        );
    }

    private function getBookingsForDate(DateTimeImmutable $targetDate, $dateField)
    {
        $query = new DbQuery();
        $query->select('hbd.`id`, hbd.`id_room`, hbd.`room_type_name`, hbd.`hotel_name`, hbd.`date_from`, hbd.`date_to`, CONCAT(c.`firstname`, " ", c.`lastname`) AS guest_name');
        $query->from('htl_booking_detail', 'hbd');
        $query->innerJoin('customer', 'c', 'c.`id_customer` = hbd.`id_customer`');
        $query->where('DATE(hbd.`' . pSQL($dateField) . '`) = "' . pSQL($targetDate->format('Y-m-d')) . '"');
        $query->where('IFNULL(hbd.`is_cancelled`, 0) = 0');
        $query->where('IFNULL(hbd.`is_refunded`, 0) = 0');

        return Db::getInstance()->executeS($query) ?: array();
    }

    private function createDateTime(DateTimeImmutable $date, $hour, $minute)
    {
        return $date->setTime((int) $hour, (int) $minute, 0);
    }

    private function encodeMetadata(array $data)
    {
        $encoded = json_encode($data);
        if ($encoded === false) {
            return null;
        }

        return $encoded;
    }
}
