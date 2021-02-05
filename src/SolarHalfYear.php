<?php

namespace hulang\calendar;

use DateTime;
use Exception;

date_default_timezone_set('PRC');
bcscale(12);

/**
 * 阳历半年
 * @package hulang\calendar
 */
class SolarHalfYear
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
   * 一个半年的月数
   * @var int
   */
  public static $MONTH_COUNT = 6;

  function __construct($year, $month)
  {
    $this->year = $year;
    $this->month = $month;
  }

  public function toString()
  {
    return $this->year . '.' . $this->getIndex();
  }

  public function __toString()
  {
    return $this->toString();
  }

  public function toFullString()
  {
    return $this->year . '年' . (1 === $this->getIndex() ? '上' : '下') . '半年';
  }

  /**
   * 通过指定年月获取阳历半年
   * @param int $year 年
   * @param int $month 月，1到12
   * @return SolarHalfYear
   */
  public static function fromYm($year, $month)
  {
    return new SolarHalfYear($year, $month);
  }

  /**
   * 通过指定日期获取阳历半年
   * @param DateTime $date 日期DateTime
   * @return SolarHalfYear
   */
  public static function fromDate($date)
  {
    $year = (int)date_format($date, 'Y');
    $month = (int)date_format($date, 'n');
    return new SolarHalfYear($year, $month);
  }

  public function getYear()
  {
    return $this->year;
  }

  public function getMonth()
  {
    return $this->month;
  }

  /**
   * 获取当月是第几半年，从1开始
   * @return int
   */
  public function getIndex()
  {
    return (int)ceil($this->month / SolarHalfYear::$MONTH_COUNT);
  }

  /**
   * 获取本半年的月份
   * @return array
   */
  public function getMonths()
  {
    $l = [];
    $index = $this->getIndex() - 1;
    for ($i = 0; $i < SolarHalfYear::$MONTH_COUNT; $i++) {
      $l[] = new SolarHalfYear($this->year, SolarHalfYear::$MONTH_COUNT * $index + $i + 1);
    }
    return $l;
  }

  /**
   * 半年推移
   * @param int $halfYears 推移的半年数，负数为倒推
   * @return SolarHalfYear|null
   */
  public function next($halfYears)
  {
    if (0 === $halfYears) {
      return new SolarHalfYear($this->year, $this->month);
    }
    try {
      $date = new DateTime($this->year . '-' . $this->month . '-1');
    } catch (Exception $e) {
      return null;
    }
    $date->modify((SolarHalfYear::$MONTH_COUNT * $halfYears) . ' month');
    return SolarHalfYear::fromDate($date);
  }
}
