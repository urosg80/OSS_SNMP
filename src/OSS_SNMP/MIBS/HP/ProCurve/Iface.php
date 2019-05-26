<?php

namespace OSS_SNMP\MIBS\HP\ProCurve;

class Iface extends \OSS_SNMP\MIBS\Foundry
{

	public function portStatus(int $port)
	{
		$data = $this->getSNMP()->get(".1.3.6.1.2.1.2.2.1.8.".$port);
		if ($data == 1) return "up";
		if ($data == 2) return "down";
		return "";
	}

	public function portsStatus()
	{
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.3");
		$ports = [];
		foreach ($data as $idx => $type)
			if ($type == 6) $ports[$idx] = ['port' => $idx, 'oid' => '.1.3.6.1.2.1.2.2.1.8.'.$idx];
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.8");
		foreach ($data as $idx => $type) {
			if (!isset($ports[$idx])) continue;

			if ($type === 1) $ports[$idx]['state'] = 'up';
			if ($type === 2) $ports[$idx]['state'] = 'down';
		}
		return $ports;
	}

	public function ports()
	{
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.3");
		$ports = [];
		foreach ($data as $idx => $type)
			if ($type == 6) $ports[$idx] = ['port' => $idx, 'oid' => '.1.3.6.1.2.1.2.2.1.8.'.$idx];

		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.2");
		foreach ($data as $idx => $desc)
		{
			if (!isset($ports[$idx])) continue;
			$ports[$idx]['desc'] = $desc;
		}
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.7");
		foreach ($data as $idx => $type)
		{
			if (!isset($ports[$idx])) continue;
			if ($type === 2) $ports[$idx]['state'] = 'disabled';
		}
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.8");
		foreach ($data as $idx => $type) {
			if (!isset($ports[$idx])) continue;

			if ($type === 1) $ports[$idx]['state'] = 'up';
			if ($type === 2) $ports[$idx]['state'] = 'down';
		}
		// PVID .1.3.6.1.2.1.17.7.1.4.5.1.1
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.17.7.1.4.5.1.1");
		foreach ($data as $idx => $row)
		{
			if (!isset($ports[$idx])) continue;

			$ports[$idx]['pvid'] = $row;
		}
		return $ports;
	}

	public function vlans()
	{
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.3");
		$vlans = [];
		$vlanOffset = 0;
		foreach ($data as $idx => $type)
		{
			if ($type == 53)
			{
				if (!$vlanOffset) $vlanOffset = $idx -1;
				$vlans[$idx - $vlanOffset] = ['vlan' => $idx - $vlanOffset, 'oid' => '.1.3.6.1.2.1.2.2.1.3.'.$idx];
			}
		}
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.2.2.1.2");
		foreach ($data as $idx => $desc)
		{
			if (!isset($vlans[$idx - $vlanOffset])) continue;
			$vlans[$idx - $vlanOffset]['desc'] = $desc;
		}
		// UNTAGGED .1.3.6.1.2.1.17.7.1.4.2.1.5
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.17.7.1.4.2.1.5");
		foreach ($data as $idx => $row)
			$vlans[$idx]['untagged'] = self::decodePortList($row);
		// TAGGED .1.3.6.1.2.1.17.7.1.4.2.1.4
		$data = $this->getSNMP()->walk1d(".1.3.6.1.2.1.17.7.1.4.2.1.4");
		foreach ($data as $idx => $row)
			$vlans[$idx]['tagged'] = array_diff(self::decodePortList($row), $vlans[$idx]['untagged']);
		return $vlans;
	}

	private static function decodePortList($hexData)
	{
		$idx = 0;
		$ports = [];// ['raw' => $hexData];
		$str = $hexData;
		while ($str) {
			$num = (int)hexdec(substr($str,0,2));
			//echo $idx." - ".$str." - ".$num." - ".($num & 0b10000000)." - ".(240 & 128)."<br/>";
			$str = substr($str, 2);
			if (($num & 128) != 0) $ports[] = $idx + 1;
			if (($num & 64) != 0) $ports[] = $idx + 2;
			if (($num & 32) != 0) $ports[] = $idx + 3;
			if (($num & 16) != 0) $ports[] = $idx + 4;
			if (($num & 8) != 0) $ports[] = $idx + 5;
			if (($num & 4) != 0) $ports[] = $idx + 6;
			if (($num & 2) != 0) $ports[] = $idx + 7;
			if (($num & 1) != 0) $ports[] = $idx + 8;
			$idx = $idx + 8;
		}
		//echo print_r($ports, true)."<br>";
		return $ports;
	}
}