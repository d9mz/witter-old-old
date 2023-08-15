<?php
namespace Witter\Models;

/**
 * BITWISE FLAGS for Custom PHP Objects
 * 
 * @link http://php.net/manual/en/language.operators.bitwise.php#108679
 * 
 * Sometimes I need a custom PHP Object that holds several boolean TRUE or FALSE values.
 * I could easily include a variable for each of them, but as always, code has a way to
 * get unweildy pretty fast. A more intelligent approach always seems to be the answer,
 * even if it seems to be overkill at first. I start with an abstract base class which will
 * hold a single integer variable called $flags. This simple integer can hold 32 TRUE
 * or FALSE boolean values. Another thing to consider is to just set certain BIT values
 * without disturbing any of the other BITS -- so included in the class definition is the
 * setFlag($flag, $value) function, which will set only the chosen bit.
 */
abstract class BitwiseFlag
{
  protected $flags;

  /*
   * Note: these functions are protected to prevent outside code
   * from falsely setting BITS. See how the extending class 'User'
   * handles this.
   *
   */
  protected function isFlagSet($flag)
  {
    return (($this->flags & $flag) == $flag);
  }

  protected function setFlag($flag, $value)
  {
    if($value)
    {
      $this->flags |= $flag;
    }
    else
    {
      $this->flags &= ~$flag;
    }
  }
}