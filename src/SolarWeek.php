<?php

namespace hulang\calendar;

use hulang\calendar\util\SolarUtil;
use DateTime;
use Exception;

date_default_timezone_set('PRC');
bcscale(12);

/**
 * 阳历周
 * @package hulang\calendar
 */
class SolarWeek
{

  /**
   * 年
   * @var int
   */
  private $year;

  /**
   * 月
   * @var int
   */
  private $month;

  /**
   * 日
   * @var int
   */
  private $day;

  /**
   * 星期几作为一周的开始，1234560分别代表星期一至星期天
   * @var int
   */
  private $start;

  function __construct($year, $month, $day, $start)
  {
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;
    $this->start = $start;
  }

  public function toString()
  {
    return $this->year . '.' . $this->month . '.' . $this->getIndex();
  }

  public function __toString()
  {
    return $this->toString();
  }

  public function toFullString()
  {
    return $this->year . '年' . $this->month . '月第' . $this->getIndex() . '周';
  }

  /**
   * 通过指定年月日获取阳历周
   * @param int $year 年
   * @param int $month 月，1到12
   * @param int $day 日，1到31
   * @param int $start 星期几作为一周的开始，1234560分别代表星期一至星期天
   * @return SolarWeek
   */
  public static function fromYmd($year, $month, $day, $start)
  {
    return new SolarWeek($year, $month, $day, $start);
  }

  /**
   * 通过指定日期获取阳历周
   * @param DateTime $date 日期DateTime
   * @param int $start 星期几作为一周的开始，1234560分别代表星期一至星期天
   * @return SolarWeek
   */
  public static function fromDate($date, $start)
  {
    $year = (int)date_format($date, 'Y');
    $month = (int)date_format($date, 'n');
    $day = (int)date_format($date, 'j');
    return new SolarWeek($year, $month, $day, $start);
  }

  public function getYear()
  {
    return $this->year;
  }

  public function getMonth()
  {
    return $this->month;
  }

  public function getDay()
  {
    return $this->day;
  }

  public function getStart()
  {
    return $this->start;
  }

  /**
   * 获取当前日期是在当月第几周
   * @return int
   */
  public function getIndex()
  {
    $firstDayWeek = (int)date('w', strtotime($this->year . '-' . $this->month . '-1'));
    if ($firstDayWeek === 0) {
      $firstDayWeek = 7;
    }
    return ceil(($this->day + $firstDayWeek - $this->start) / 7);
  }

  /**
   * 周推移
   * @param int $weeks 推移的周数，负数为倒推
   * @param bool $separateMonth 是否按月单独计算
   * @return SolarWeek|null
   */
  public function next($weeks, $separateMonth)
  {
    if (0 === $weeks) {
      return SolarWeek::fromYmd($this->year, $this->month, $this->day, $this->start);
    }
    if ($separateMonth) {
      $n = $weeks;
      try {
        $date = new DateTime($this->year . '-' . $this->month . '-' . $this->day);
      } catch (Exception $e) {
        return null;
      }
      $week = SolarWeek::fromDate($date, $this->start);
      $month = $this->month;
      $plus = $n > 0;
      while (0 !== $n) {
        $date->modify(($plus ? 7 : -7) . ' day');
        $week = SolarWeek::fromDate($date, $this->start);
        $weekMonth = $week->getMonth();
        if ($month !== $weekMonth) {
          $index = $week->getIndex();
          if ($plus) {
            if (1 === $index) {
              $firstDay = $week->getFirstDay();
              $week = SolarWeek::fromYmd($firstDay->getYear(), $firstDay->getMonth(), $firstDay->getDay(), $this->start);
              $weekMonth = $week->getMonth();
            } else {
              try {
                $date = new DateTime($week->year . '-' . $week->month . '-1');
              } catch (Exception $e) {
                return null;
              }
              $week = SolarWeek::fromDate($date, $this->start);
            }
          } else {
            $size = SolarUtil::getWeeksOfMonth($week->getYear(), $week->getMonth(), $week->getStart());
            if ($size === $index) {
              $lastDay = $week->getFirstDay()->next(6);
              $week = SolarWeek::fromYmd($lastDay->getYear(), $lastDay->getMonth(), $lastDay->getDay(), $this->start);
              $weekMonth = $week->getMonth();
            } else {
              try {
                $date = new DateTime($this->year . '-' . $this->month . '-' . SolarUtil::getDaysOfMonth($week->getYear(), $week->getMonth()));
              } catch (Exception $e) {
                return null;
              }
              $week = SolarWeek::fromDate($date, $this->start);
            }
          }
          $month = $weekMonth;
        }
        $n -= $plus ? 1 : -1;
      }
      return $week;
    } else {
      try {
        $date = new DateTime($this->year . '-' . $this->month . '-' . $this->day);
      } catch (Exception $e) {
        return null;
      }
      $date->modify(($weeks * 7) . ' day');
      return SolarWeek::fromDate($date, $this->start);
    }
  }

  /**
   * 获取本周第一天的阳历日期（可能跨月）
   * @return Solar|null
   */
  public function getFirstDay()
  {
    try {
      $date = new DateTime($this->year . '-' . $this->month . '-' . $this->day);
    } catch (Exception $e) {
      return null;
    }
    $week = (int)$date->format('w');
    $prev = $week - $this->start;
    if ($prev < 0) {
      $prev += 7;
    }
    $date->modify(-$prev . ' day');
    return Solar::fromDate($date);
  }

  /**
   * 获取本周第一天的阳历日期（仅限当月）
   * @return Solar|null
   */
  public function getFirstDayInMonth()
  {
    $days = $this->getDays();
    foreach ($days as $day) {
      if ($this->month === $day->getMonth()) {
        return $day;
      }
    }
    return null;
  }

  /**
   * 获取本周的阳历日期列表（可能跨月）
   * @return array
   */
  public function getDays()
  {
    $firstDay = $this->getFirstDay();
    $l = [];
    if (null == $firstDay) {
      return $l;
    }
    $l[] = $firstDay;
    for ($i = 1; $i < 7; $i++) {
      $l[] = $firstDay->next($i);
    }
    return $l;
  }

  /**
   * 获取本周的阳历日期列表（仅限当月）
   * @return array
   */
  public function getDaysInMonth()
  {
    $days = $this->getDays();
    $l = [];
    foreach ($days as $day) {
      if ($this->month !== $day->getMonth()) {
        continue;
      }
      $l[] = $day;
    }
    return $l;
  }
}
