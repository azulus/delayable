<?php

namespace delayable;

interface Delayable {
	public function loadDelayed($methods);
}
